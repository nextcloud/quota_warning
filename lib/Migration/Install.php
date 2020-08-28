<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @license GNU AGPL version 3 or any later version
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
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class Install implements IRepairStep {

	/** @var IUserManager */
	protected $userManager;

	/** @var IJobList */
	protected $jobList;

	/**
	 * Install constructor.
	 *
	 * @param IUserManager $userManager
	 * @param IJobList $jobList
	 */
	public function __construct(IUserManager $userManager, IJobList $jobList) {
		$this->userManager = $userManager;
		$this->jobList = $jobList;
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
		$output->startProgress();
		$this->userManager->callForSeenUsers(function(IUser $user) use($output) {
			$this->jobList->add(
				User::class,
				['uid' => $user->getUID()]
			);
			$output->advance();
		});
		$output->finishProgress();
	}
}
