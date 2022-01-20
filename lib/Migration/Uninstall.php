<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Carl Schwan <carl@carlschwan.eu>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
