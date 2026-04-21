<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Notification;

use OCA\QuotaWarning\AppInfo\Application;
use OCA\QuotaWarning\CheckQuota;
use OCP\AppFramework\Services\IAppConfig;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;
use OCP\Notification\UnknownNotificationException;

class Notifier implements INotifier {
	public function __construct(
		private CheckQuota $checkQuota,
		private IAppConfig $appConfig,
		private IFactory $l10nFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	#[\Override]
	public function getID(): string {
		return Application::APP_ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Quota warning');
	}

	#[\Override]
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			// Wrong app
			throw new UnknownNotificationException('Unknown app');
		}

		$usage = $this->checkQuota->getRelativeQuotaUsage($notification->getUser());
		if ($usage < $this->appConfig->getAppValueInt('info_percentage', 85)) {
			// User is not in danger zone anymore
			throw new AlreadyProcessedException();
		}

		if ($usage > $this->appConfig->getAppValueInt('alert_percentage', 95)) {
			$imagePath = $this->urlGenerator->imagePath(Application::APP_ID, 'app-alert.svg');
		} elseif ($usage > $this->appConfig->getAppValueInt('warning_percentage', 90)) {
			$imagePath = $this->urlGenerator->imagePath(Application::APP_ID, 'app-warning.svg');
		} else {
			$imagePath = $this->urlGenerator->imagePath(Application::APP_ID, 'app-dark.svg');
		}

		$notification->setIcon($this->urlGenerator->getAbsoluteURL($imagePath));

		$link = $this->appConfig->getAppValueString('plan_management_url');
		if ($link) {
			$notification->setLink($link);
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

		$parameters = $notification->getSubjectParameters();
		$usage = (int)round((float)$parameters['usage']);
		$usage = min(100, $usage);
		$notification->setParsedSubject(
			$l->t('You are using more than %d%% of your storage quota', [$usage])
		);
		return $notification;
	}
}
