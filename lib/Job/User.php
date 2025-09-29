<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Job;

use OCA\QuotaWarning\CheckQuota;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;

class User extends TimedJob {
	public function __construct(
		ITimeFactory $time,
		protected CheckQuota $checkQuota,
	) {
		parent::__construct($time);
		$this->setInterval(86400);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
	}

	#[\Override]
	protected function run($argument): void {
		$this->checkQuota->check($argument['uid']);
	}
}
