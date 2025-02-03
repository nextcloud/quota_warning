<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Migration;

use OCA\QuotaWarning\Job\User;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

/**
 * Repair step called when disabling the quota_warning application. This will
 * remove all the jobs from the job lists.
 *
 * @author Carl Schwan <carl@carlschwan.eu>
 */
class Uninstall implements IRepairStep {

	/** @var IJobList */
	private $jobList;

	public function __construct(IJobList $jobList) {
		$this->jobList = $jobList;
	}

	public function getName() {
		return 'Remove QuotaWarning background jobs';
	}

	public function run(IOutput $output) {
		// Remove all the background jobs
		$this->jobList->remove(User::class);
	}
}
