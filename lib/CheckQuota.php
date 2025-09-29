<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning;

use OCA\QuotaWarning\AppInfo\Application;
use OCA\QuotaWarning\Job\User;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Notification\IManager;
use Psr\Log\LoggerInterface;

class CheckQuota {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IConfig $config,
		private readonly LoggerInterface $logger,
		private readonly IMailer $mailer,
		private readonly IFactory $l10nFactory,
		private readonly IUserManager $userManager,
		private readonly IJobList $jobList,
		private readonly IManager $notificationManager,
	) {
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

		if ($usage > $this->appConfig->getAppValueInt('alert_percentage', 95)) {
			if ($this->shouldIssueWarning($userId, 'alert')) {
				$this->issueWarning($userId, $usage);
				if ($this->appConfig->getAppValueBool('alert_email')) {
					$this->sendEmail($userId, $usage);
				}
			}
			$this->updateLastWarning($userId, 'alert');
		} elseif ($usage > $this->appConfig->getAppValueInt('warning_percentage', 90)) {
			if ($this->shouldIssueWarning($userId, 'warning')) {
				$this->issueWarning($userId, $usage);
				if ($this->appConfig->getAppValueBool('warning_email')) {
					$this->sendEmail($userId, $usage);
				}
			}
			$this->updateLastWarning($userId, 'warning');
			$this->removeLastWarning($userId, 'alert');
		} elseif ($usage > $this->appConfig->getAppValueInt('info_percentage', 85)) {
			if ($this->shouldIssueWarning($userId, 'info')) {
				$this->issueWarning($userId, $usage);
				if ($this->appConfig->getAppValueBool('info_email')) {
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
		} catch (NotFoundException) {
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
			$this->logger->critical($e->getMessage(), ['app' => Application::APP_ID, 'exception' => $e]);
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

		$link = $this->appConfig->getAppValueString('plan_management_url');

		$help = $l->t('You are using more than %d%% of your storage quota. Try to free up some space by deleting old files you don\'t need anymore.', [$percentage]);
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
			$this->logger->critical($e->getMessage(), ['app' => Application::APP_ID, 'exception' => $e]);
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
			$this->logger->critical($e->getMessage(), ['app' => Application::APP_ID, 'exception' => $e]);
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

		$days = $this->appConfig->getAppValueInt('repeat_warning', 7);

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
