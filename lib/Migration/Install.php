<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Migration;

use OCA\QuotaWarning\Job\User;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Install implements IRepairStep {
	public function __construct(
		protected IUserManager $userManager,
		protected IJobList $jobList,
		protected IAppConfig $appConfig,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Add background jobs for existing users';
	}

	#[\Override]
	public function run(IOutput $output): void {
		if ($this->appConfig->getAppValueBool('initialised')) {
			return;
		}

		$output->startProgress();
		$this->userManager->callForSeenUsers(function (IUser $user) use ($output) {
			$this->jobList->add(
				User::class,
				['uid' => $user->getUID()]
			);
			$output->advance();
		});
		$output->finishProgress();

		$this->appConfig->setAppValueBool('initialised', true);
	}
}
