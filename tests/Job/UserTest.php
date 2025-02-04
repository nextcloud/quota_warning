<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Tests\Job;

use OCA\QuotaWarning\CheckQuota;
use OCA\QuotaWarning\Job\User;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;

class UserTest extends \Test\TestCase {
	/** @var User */
	protected $job;

	/** @var ITimeFactory|MockObject */
	protected $time;
	/** @var CheckQuota|MockObject */
	protected $checkQuota;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->checkQuota = $this->createMock(CheckQuota::class);

		$this->job = new User(
			$this->time,
			$this->checkQuota
		);
	}

	public function testInterval(): void {
		$this->assertSame(86400, self::invokePrivate($this->job, 'interval'));
	}

	public function testRun(): void {
		$this->checkQuota->expects($this->once())
			->method('check')
			->with('test1');
		self::invokePrivate($this->job, 'run', [['uid' => 'test1']]);
	}
}
