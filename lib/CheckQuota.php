<?php
/**
 * @copyright Copyright (c) 2017 Joas Schilling <coding@schilljs.com>
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

namespace OCA\QuotaWarning;

use OCA\QuotaWarning\AppInfo\Application;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\Notification\IManager;

class CheckQuota {

	const ALERT = 90;
	const WARNING = 70;
	const INFO = 50;

	/** @var IConfig */
	protected $config;

	/** @var ILogger */
	protected $logger;

	/** @var IManager */
	protected $notificationManager;

	/**
	 * CheckQuota constructor.
	 *
	 * @param IConfig $config
	 * @param ILogger $logger
	 * @param IManager $notificationManager
	 */
	public function __construct(IConfig $config, ILogger $logger, IManager $notificationManager) {
		$this->config = $config;
		$this->logger = $logger;
		$this->notificationManager = $notificationManager;
	}

	/**
	 * @param string $userId
	 */
	public function check($userId) {
		try {
			$storage = $this->getStorageInfo($userId);
		} catch (NotFoundException $e) {
			return;
		}

		if ($storage['quota'] === FileInfo::SPACE_UNLIMITED || $storage['quota'] === 0) {
			return;
		}

		if ($storage['relative'] > self::ALERT) {
			if ($this->shouldWarn($userId, self::ALERT)) {
				$this->issueWarning($userId, $storage['relative']);
			}
			$this->updateLastWarning($userId, self::ALERT);

		} else if ($storage['relative'] > self::WARNING) {
			if ($this->shouldWarn($userId, self::WARNING)) {
				$this->issueWarning($userId, $storage['relative']);
			}
			$this->updateLastWarning($userId, self::WARNING);
			$this->removeLastWarning($userId, self::ALERT);

		} else if ($storage['relative'] > self::INFO) {
			if ($this->shouldWarn($userId, self::INFO)) {
				$this->issueWarning($userId, $storage['relative']);
			}
			$this->updateLastWarning($userId, self::INFO);
			$this->removeLastWarning($userId, self::WARNING);

		} else {
			$this->removeLastWarning($userId, self::INFO);

		}
	}

	/**
	 * @param string $userId
	 * @param float $percentage
	 */
	protected function issueWarning($userId, $percentage) {
		$notification = $this->notificationManager->createNotification();

		try {
			$notification->setApp(Application::APP_ID)
				->setObject('quota', $userId)
				->setUser($userId)
				->setDateTime(new \DateTime())
				->setSubject(Application::APP_ID, ['usage' => $percentage]);
			$this->notificationManager->notify($notification);
		} catch (\InvalidArgumentException $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}

	/**
	 * @param string $userId
	 * @param int $level
	 * @return bool
	 */
	protected function shouldWarn($userId, $level) {
		$lastWarning = $this->config->getUserValue($userId, Application::APP_ID, 'warning-' . $level, '');
		if ($lastWarning === '') {
			return true;
		}

		$dateLastWarning = \DateTime::createFromFormat(\DateTime::ATOM, $lastWarning);
		$dateLastWarning->add(new \DateInterval('P7D'));
		$now = new \DateTime();
		return $dateLastWarning < $now;
	}

	/**
	 * @param string $userId
	 * @param int $level
	 */
	protected function updateLastWarning($userId, $level) {
		$now = new \DateTime();
		$dateTimeString = $now->format(\DateTime::ATOM);
		switch ($level) {
			case self::ALERT:
				$this->config->setUserValue($userId, Application::APP_ID, 'warning-' . self::ALERT, $dateTimeString);
			case self::WARNING:
				$this->config->setUserValue($userId, Application::APP_ID, 'warning-' . self::WARNING, $dateTimeString);
			case self::INFO:
				$this->config->setUserValue($userId, Application::APP_ID, 'warning-' . self::INFO, $dateTimeString);
		}
	}

	/**
	 * @param string $userId
	 * @param int $level
	 */
	protected function removeLastWarning($userId, $level) {
		switch ($level) {
			case self::INFO:
				$this->config->deleteUserValue($userId, Application::APP_ID, 'warning-' . self::INFO);
			case self::WARNING:
				$this->config->deleteUserValue($userId, Application::APP_ID, 'warning-' . self::WARNING);
			case self::ALERT:
				$this->config->deleteUserValue($userId, Application::APP_ID, 'warning-' . self::ALERT);
		}
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	protected function getStorageInfo($userId) {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($userId);
		return \OC_Helper::getStorageInfo('/');
	}
}
