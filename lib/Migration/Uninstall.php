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

class Uninstall implements IRepairStep {
	public function __construct(
		private readonly IJobList $jobList,
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Remove QuotaWarning background jobs';
	}

	#[\Override]
	public function run(IOutput $output): void {
		// Remove all the background jobs
		$this->jobList->remove(User::class);
	}
}
