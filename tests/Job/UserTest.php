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
