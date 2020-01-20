<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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

namespace OCA\QuotaWarning\Tests\Migration;

use OCA\QuotaWarning\Job\User;
use OCA\QuotaWarning\Migration\Install;
use OCP\BackgroundJob\IJobList;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;

class InstallTest extends \Test\TestCase {
	/** @var Install */
	protected $migration;

	/** @var IUserManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $userManager;
	/** @var IJobList|\PHPUnit_Framework_MockObject_MockObject */
	protected $jobList;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->migration = new Install(
			$this->userManager,
			$this->jobList
		);
	}

	public function testGetName() {
		$this->assertInternalType('string', $this->migration->getName());
	}

	protected function getUser($uid) {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn($uid);
		return $user;
	}

	public function testRun() {
		/** @var IOutput|\PHPUnit_Framework_MockObject_MockObject $output */
		$output = $this->createMock(IOutput::class);
		$output->expects($this->once())
			->method('startProgress');
		$output->expects($this->once())
			->method('finishProgress');

		$this->userManager->expects($this->once())
			->method('callForSeenUsers')
			->willReturnCallback(function ($closure) {
				$closure($this->getUser('test1'));
				$closure($this->getUser('test2'));
				$closure($this->getUser('test3'));
			});

		$this->jobList->expects($this->exactly(3))
			->method('add')
			->withConsecutive(
				[User::class, ['uid' => 'test1']],
				[User::class, ['uid' => 'test2']],
				[User::class, ['uid' => 'test3']]
			);

		$this->migration->run($output);
	}
}
