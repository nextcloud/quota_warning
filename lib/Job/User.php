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

	/** @var CheckQuota */
	protected $checkQuota;

	public function __construct(ITimeFactory $time,
		CheckQuota $checkQuota) {
		parent::__construct($time);
		$this->checkQuota = $checkQuota;
		$this->setInterval(86400);

		if (method_exists($this, 'setTimeSensitivity')) {
			/**
			 * This constant is always defined when setTimeSensitivity exists,
			 * Psalm can not know this :(
			 * @psalm-suppress UndefinedConstant
			 */
			$this->setTimeSensitivity(TimedJob::TIME_INSENSITIVE);
		}
	}

	#[\Override]
	protected function run($argument): void {
		$this->checkQuota->check($argument['uid']);
	}
}
