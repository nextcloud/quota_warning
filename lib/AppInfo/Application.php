<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\AppInfo;

use OCA\QuotaWarning\Job\User;
use OCA\QuotaWarning\Notification\Notifier;
use OCA\QuotaWarning\SettingsForm;
use OCP\AppFramework\App;
use OCP\AppFramework\Bootstrap\IBootContext;
use OCP\AppFramework\Bootstrap\IBootstrap;
use OCP\AppFramework\Bootstrap\IRegistrationContext;
use OCP\Util;

class Application extends App implements IBootstrap {
	public const APP_ID = 'quota_warning';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	#[\Override]
	public function register(IRegistrationContext $context): void {
		$context->registerDeclarativeSettings(SettingsForm::class);
		$context->registerNotifierService(Notifier::class);
	}

	#[\Override]
	public function boot(IBootContext $context): void {
		$this->registerLoginHook();
	}


	protected function registerLoginHook(): void {
		Util::connectHook('OC_User', 'post_login', $this, 'loginHook');
	}

	public function loginHook(array $params): void {
		if (!isset($params['uid'])) {
			return;
		}

		$jobList = \OCP\Server::get(\OCP\BackgroundJob\IJobList::class);
		$jobList->add(
			User::class,
			['uid' => $params['uid']]
		);
	}
}
