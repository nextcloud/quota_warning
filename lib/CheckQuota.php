<?php

declare(strict_types=1);
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
use OCA\QuotaWarning\Job\User;
use OCP\BackgroundJob\IJobList;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\ILogger;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Notification\IManager;

class CheckQuota {

	/** @var IConfig */
	protected $config;

	/** @var ILogger */
	protected $logger;

	/** @var IMailer */
	protected $mailer;

	/** @var IFactory */
	protected $l10nFactory;

	/** @var IUserManager */
	protected $userManager;

	/** @var IJobList */
	protected $jobList;

	/** @var IManager */
	protected $notificationManager;

	public function __construct(IConfig $config,
								ILogger $logger,
								IMailer $mailer,
								IFactory $l10nFactory,
								IUserManager $userManager,
								IJobList $jobList,
								IManager $notificationManager) {
		$this->config = $config;
		$this->logger = $logger;
		$this->mailer = $mailer;
		$this->l10nFactory = $l10nFactory;
		$this->userManager = $userManager;
		$this->jobList = $jobList;
		$this->notificationManager = $notificationManager;
	}

	/**
	 * Checks the quota of a given user and issues the warning if necessary
	 *
	 * @param string $userId
	 */
	public function check(string $userId): void {
		if (!$this->userManager->userExists($userId)) {
			$this->jobList->remove(User::class, ['uid' => $userId]);
			return;
		}

		$usage = $this->getRelativeQuotaUsage($userId);

		if ($usage > $this->config->getAppValue('quota_warning', 'alert_percentage', 95)) {
			if ($this->shouldIssueWarning($userId, 'alert')) {
				$this->issueWarning($userId, $usage);
				if ($this->config->getAppValue('quota_warning', 'alert_email', 'no') === 'yes') {
					$this->sendEmail($userId, $usage);
				}
			}
			$this->updateLastWarning($userId, 'alert');
		} elseif ($usage > $this->config->getAppValue('quota_warning', 'warning_percentage', 90)) {
			if ($this->shouldIssueWarning($userId, 'warning')) {
				$this->issueWarning($userId, $usage);
				if ($this->config->getAppValue('quota_warning', 'warning_email', 'no') === 'yes') {
					$this->sendEmail($userId, $usage);
				}
			}
			$this->updateLastWarning($userId, 'warning');
			$this->removeLastWarning($userId, 'alert');
		} elseif ($usage > $this->config->getAppValue('quota_warning', 'info_percentage', 85)) {
			if ($this->shouldIssueWarning($userId, 'info')) {
				$this->issueWarning($userId, $usage);
				if ($this->config->getAppValue('quota_warning', 'info_email', 'no') === 'yes') {
					$this->sendEmail($userId, $usage);
				}
			}
			$this->updateLastWarning($userId, 'info');
			$this->removeLastWarning($userId, 'warning');
		} else {
			$this->removeWarning($userId);
			$this->removeLastWarning($userId, 'info');
		}
	}

	/**
	 * @param string $userId
	 * @return float
	 */
	public function getRelativeQuotaUsage(string $userId): float {
		try {
			$storage = $this->getStorageInfo($userId);
		} catch (NotFoundException $e) {
			return 0.0;
		}

		if ($storage['quota'] === FileInfo::SPACE_UNLIMITED || $storage['quota'] < 5 * 1024 ** 2) {
			// No warnings for unlimited storage and for less than 5 MB
			return 0.0;
		}

		return $storage['relative'];
	}

	/**
	 * Issues the warning by creating a notification
	 *
	 * @param string $userId
	 * @param float $percentage
	 */
	protected function issueWarning(string $userId, float $percentage): void {
		$this->removeWarning($userId);
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
	 * Send an email to the user
	 *
	 * @param string $userId
	 * @param float $percentage
	 */
	protected function sendEmail(string $userId, float $percentage): void {
		$user = $this->userManager->get($userId);
		if (!$user instanceof IUser) {
			return;
		}

		$email = $user->getEMailAddress();
		if (!$email) {
			return;
		}

		$lang = $this->config->getUserValue($userId, 'core', 'lang');
		$l = $this->l10nFactory->get('quota_warning', $lang);
		$emailTemplate = $this->mailer->createEMailTemplate('quota_warning.Notification', [
			'quota' => $percentage,
			'userId' => $user->getUID()
		]);

		$emailTemplate->addHeader();
		$emailTemplate->addHeading($l->t('Nearing your storage quota'), false);

		$link = $this->config->getAppValue('quota_warning', 'plan_management_url');

		$help = $l->t('You are using more than %d%% of your storage quota. Try to free up some space by deleting old files you don\'t need anymore.', $percentage);
		if ($link !== '') {
			$emailTemplate->addBodyText(
				htmlspecialchars($help . ' ' . $l->t('Or click the following button for options to change your data plan.')),
				$help . ' ' . $l->t('Or click the following link for options to change your data plan.')
			);

			$emailTemplate->addBodyButton(
				htmlspecialchars($l->t('Data plan options')),
				$link,
				false
			);
		} else {
			$emailTemplate->addBodyText($help);
		}

		$emailTemplate->addFooter();

		try {
			$message = $this->mailer->createMessage();
			$message->setTo([$email => $user->getUID()]);
			$message->setSubject($l->t('Nearing your storage quota'));
			$message->setPlainBody($emailTemplate->renderText());
			$message->setHtmlBody($emailTemplate->renderHtml());
			$this->mailer->send($message);
		} catch (\Exception $e) {
			$this->logger->logException($e, ['app' => 'quota_warning']);
		}
	}

	/**
	 * Removes any existing warning
	 *
	 * @param string $userId
	 */
	protected function removeWarning(string $userId): void {
		$notification = $this->notificationManager->createNotification();

		try {
			$notification->setApp(Application::APP_ID)
				->setObject('quota', $userId)
				->setUser($userId);
			$this->notificationManager->markProcessed($notification);
		} catch (\InvalidArgumentException $e) {
			$this->logger->logException($e, ['app' => Application::APP_ID]);
		}
	}

	/**
	 * The user should be warned, when we was not warned in the last 7 days
	 *
	 * @param string $userId
	 * @param string $level
	 * @return bool
	 */
	protected function shouldIssueWarning(string $userId, string $level): bool {
		$lastWarning = $this->config->getUserValue($userId, Application::APP_ID, 'warning-' . $level, '');
		if ($lastWarning === '') {
			return true;
		}

		$days = (int) $this->config->getAppValue('quota_warning', 'repeat_warning', 7);

		if ($days <= 0) {
			return false;
		}

		$dateLastWarning = \DateTime::createFromFormat(\DateTimeInterface::ATOM, $lastWarning);
		$dateLastWarning->add(new \DateInterval('P' . $days . 'D'));
		$now = new \DateTime();
		return $dateLastWarning < $now;
	}

	/**
	 * Updates the "last date" for all <= the given alert level
	 *
	 * @param string $userId
	 * @param string $level
	 */
	protected function updateLastWarning(string $userId, string $level): void {
		$now = new \DateTime();
		$dateTimeString = $now->format(\DateTimeInterface::ATOM);
		switch ($level) {
			case 'alert':
				$this->config->setUserValue($userId, Application::APP_ID, 'warning-alert', $dateTimeString);
				// no break
			case 'warning':
				$this->config->setUserValue($userId, Application::APP_ID, 'warning-warning', $dateTimeString);
				// no break
			case 'info':
				$this->config->setUserValue($userId, Application::APP_ID, 'warning-info', $dateTimeString);
		}
	}

	/**
	 * Removes the warnings when the user is below the level again
	 *
	 * @param string $userId
	 * @param string $level
	 */
	protected function removeLastWarning(string $userId, string $level): void {
		switch ($level) {
			case 'info':
				$this->config->deleteUserValue($userId, Application::APP_ID, 'warning-info');
				// no break
			case 'warning':
				$this->config->deleteUserValue($userId, Application::APP_ID, 'warning-warning');
				// no break
			case 'alert':
				$this->config->deleteUserValue($userId, Application::APP_ID, 'warning-alert');
		}
	}

	/**
	 * @param string $userId
	 * @return array
	 */
	protected function getStorageInfo(string $userId): array {
		\OC_Util::tearDownFS();
		\OC_Util::setupFS($userId);
		return \OC_Helper::getStorageInfo('/');
	}
}
