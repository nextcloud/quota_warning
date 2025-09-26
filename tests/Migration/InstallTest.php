<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
	protected IUserManager&MockObject $userManager;
	protected IJobList&MockObject $jobList;
	protected IConfig&MockObject $config;
	protected Install $migration;

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
		/** @var IOutput&MockObject $output */
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

		$calls = [
			[User::class, ['uid' => 'test1']],
			[User::class, ['uid' => 'test2']],
			[User::class, ['uid' => 'test3']],
		];
		$this->jobList->expects($this->exactly(3))
			->method('add')
			->willReturnCallback(function () use (&$calls) {
				$expected = array_shift($calls);
				$this->assertEquals($expected, func_get_args());
			});

		$this->migration->run($output);
	}

	public function testRunSkipped(): void {
		/** @var IOutput&MockObject $output */
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
