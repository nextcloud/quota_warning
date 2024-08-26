<?php

declare(strict_types=1);
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
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Migration\IOutput;
use PHPUnit\Framework\MockObject\MockObject;

class InstallTest extends \Test\TestCase {
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var IJobList|MockObject */
	protected $jobList;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var Install */
	protected $migration;

	protected function setUp(): void {
		parent::setUp();

		$this->userManager = $this->createMock(IUserManager::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->config = $this->createMock(IConfig::class);

		$this->migration = new Install(
			$this->userManager,
			$this->jobList,
			$this->config
		);
	}

	public function testGetName(): void {
		$this->assertIsString($this->migration->getName());
	}

	protected function getUser(string $uid): MockObject {
		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('getUID')
			->willReturn($uid);
		return $user;
	}

	public function testRun(): void {
		/** @var IOutput|MockObject $output */
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

	public function testRunSkipped(): void {
		/** @var IOutput|MockObject $output */
		$output = $this->createMock(IOutput::class);
		$output->expects($this->never())
			->method('startProgress');
		$output->expects($this->never())
			->method('finishProgress');

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('quota_warning', 'initialised', 'no')
			->willReturn('yes');

		$this->userManager->expects($this->never())
			->method('callForSeenUsers');

		$this->jobList->expects($this->never())
			->method('add');

		$this->migration->run($output);
	}
}
