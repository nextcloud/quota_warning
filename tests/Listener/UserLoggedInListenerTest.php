<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Tests\Listener;

use OCA\QuotaWarning\Job\User;
use OCA\QuotaWarning\Listener\UserLoggedInListener;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\IUser;
use OCP\User\Events\UserLoggedInEvent;
use PHPUnit\Framework\MockObject\MockObject;

class UserLoggedInListenerTest extends \Test\TestCase {
	private IJobList&MockObject $jobList;
	private UserLoggedInListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->jobList = $this->createMock(IJobList::class);
		$this->listener = new UserLoggedInListener($this->jobList);
	}

	public function testHandleIgnoresUnrelatedEvents(): void {
		$this->jobList->expects($this->never())
			->method('add');

		/** @psalm-suppress InvalidArgument */
		$this->listener->handle(new Event());
	}

	public function testHandleAddsJobForUser(): void {
		$user = $this->createMock(IUser::class);
		$user->method('getUID')->willReturn('alice');

		$event = new UserLoggedInEvent($user, 'alice', null, false);

		$this->jobList->expects($this->once())
			->method('add')
			->with(User::class, ['uid' => 'alice']);

		$this->listener->handle($event);
	}
}
