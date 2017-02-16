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

namespace OCA\QuotaWarning\AppInfo;

use OCA\QuotaWarning\Job\User;
use OCA\QuotaWarning\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\IL10N;
use OCP\Util;

class Application extends App {
	const APP_ID = 'quota_warning';

	public function __construct() {
		parent::__construct(self::APP_ID);
	}

	public function register() {
		$this->registerLoginHook();
		$this->registerNotifier();
	}


	protected function registerLoginHook() {
		Util::connectHook('OC_User', 'post_login', $this, 'login');
	}

	/**
	 * @param array $params
	 */
	public function loginHook(array $params) {
		if (!isset($params['uid'])) {
			return;
		}

		$jobList = $this->getContainer()->getServer()->getJobList();
		$jobList->add(
			User::class,
			['uid' => $params['uid']]
		);
	}

	public function registerNotifier() {
		$notificationManager = $this->getContainer()->getServer()->getNotificationManager();
		$notificationManager->registerNotifier(
			function() {
				return $this->getContainer()->query(Notifier::class);
			},
			function () {
				$l = $this->getContainer()->query(IL10N::class);
				return ['id' => self::APP_ID, 'name' => $l->t('Quota warning')];
			}
		);
	}
}
