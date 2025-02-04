<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Migration;

use OCA\QuotaWarning\AppInfo\Application;
use OCA\QuotaWarning\Job\User;
use OCP\BackgroundJob\IJobList;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Install implements IRepairStep {

	/** @var IUserManager */
	protected $userManager;

	/** @var IJobList */
	protected $jobList;
	/** @var IConfig */
	protected $config;

	public function __construct(
		IUserManager $userManager,
		IJobList $jobList,
		IConfig $config,
	) {
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->config = $config;
	}

	/**
	 * Returns the step's name
	 *
	 * @return string
	 * @since 9.1.0
	 */
	public function getName(): string {
		return 'Add background jobs for existing users';
	}

	/**
	 * @param IOutput $output
	 * @since 9.1.0
	 */
	public function run(IOutput $output): void {
		if ($this->config->getAppValue(Application::APP_ID, 'initialised', 'no') === 'yes') {
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

		$this->config->setAppValue(Application::APP_ID, 'initialised', 'yes');
	}
}
