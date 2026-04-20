<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Listener;

use OCA\QuotaWarning\Job\User;
use OCP\BackgroundJob\IJobList;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\User\Events\UserLoggedInEvent;
use Override;

/**
 * @template-implements IEventListener<UserLoggedInEvent>
 */
class UserLoggedInListener implements IEventListener {
	public function __construct(
		private IJobList $jobList,
	) {
	}

	#[Override]
	public function handle(Event $event): void {
		if (!$event instanceof UserLoggedInEvent) {
			return;
		}

		$this->jobList->add(
			User::class,
			['uid' => $event->getUser()->getUID()]
		);
	}
}
