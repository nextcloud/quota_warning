<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Notification;

use OCA\QuotaWarning\AppInfo\Application;
use OCA\QuotaWarning\CheckQuota;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\AlreadyProcessedException;
use OCP\Notification\INotification;
use OCP\Notification\INotifier;

class Notifier implements INotifier {

	/** @var CheckQuota */
	protected $checkQuota;

	/** @var IConfig */
	protected $config;

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IURLGenerator */
	protected $url;

	public function __construct(CheckQuota $checkQuota,
		IConfig $config,
		IFactory $l10nFactory,
		IURLGenerator $urlGenerator) {
		$this->checkQuota = $checkQuota;
		$this->config = $config;
		$this->l10nFactory = $l10nFactory;
		$this->url = $urlGenerator;
	}

	/**
	 * Identifier of the notifier, only use [a-z0-9_]
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getID(): string {
		return Application::APP_ID;
	}

	/**
	 * Human readable name describing the notifier
	 *
	 * @return string
	 * @since 17.0.0
	 */
	public function getName(): string {
		return $this->l10nFactory->get(Application::APP_ID)->t('Quota warning');
	}

	/**
	 * @param INotification $notification
	 * @param string $languageCode The code of the language that should be used to prepare the notification
	 * @return INotification
	 * @throws \InvalidArgumentException When the notification was not prepared by a notifier
	 */
	public function prepare(INotification $notification, string $languageCode): INotification {
		if ($notification->getApp() !== Application::APP_ID) {
			// Wrong app
			throw new \InvalidArgumentException('Unknown app');
		}

		$usage = $this->checkQuota->getRelativeQuotaUsage($notification->getUser());
		if ($usage < $this->config->getAppValue('quota_warning', 'info_percentage', '85')) {
			// User is not in danger zone anymore
			throw new AlreadyProcessedException();
		}

		if ($usage > $this->config->getAppValue('quota_warning', 'alert_percentage', '95')) {
			$imagePath = $this->url->imagePath(Application::APP_ID, 'app-alert.svg');
		} elseif ($usage > $this->config->getAppValue('quota_warning', 'warning_percentage', '90')) {
			$imagePath = $this->url->imagePath(Application::APP_ID, 'app-warning.svg');
		} else {
			$imagePath = $this->url->imagePath(Application::APP_ID, 'app-dark.svg');
		}

		$notification->setIcon($this->url->getAbsoluteURL($imagePath));

		$link = $this->config->getAppValue('quota_warning', 'plan_management_url');
		if ($link) {
			$notification->setLink($link);
		}

		// Read the language from the notification
		$l = $this->l10nFactory->get(Application::APP_ID, $languageCode);

		$parameters = $notification->getSubjectParameters();
		$usage = (int)round($parameters['usage']);
		$usage = min(100, $usage);
		$notification->setParsedSubject(
			$l->t('You are using more than %d%% of your storage quota', [$usage])
		);
		return $notification;
	}
}
