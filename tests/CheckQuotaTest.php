<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Tests;

use OCA\QuotaWarning\CheckQuota;
use OCA\QuotaWarning\Job\User;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IEMailTemplate;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Notification\IManager;
use OCP\Notification\INotification;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class CheckQuotaTest extends \Test\TestCase {
	protected IAppConfig&MockObject $appConfig;
	protected IConfig&MockObject $config;
	protected LoggerInterface&MockObject $logger;
	protected IMailer&MockObject $mailer;
	protected IFactory&MockObject $l10nFactory;
	protected IUserManager&MockObject $userManager;
	protected IJobList&MockObject $jobList;
	protected IManager&MockObject $notificationManager;

	#[Override]
	protected function setUp(): void {
		parent::setUp();

		$this->appConfig = $this->createMock(IAppConfig::class);
		$this->config = $this->createMock(IConfig::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->mailer = $this->createMock(IMailer::class);
		$this->l10nFactory = $this->createMock(IFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->jobList = $this->createMock(IJobList::class);
		$this->notificationManager = $this->createMock(IManager::class);
	}

	/**
	 * @param list<non-empty-string> $methods
	 */
	protected function getCheckQuota(array $methods = ['getRelativeQuotaUsage']): CheckQuota&MockObject {
		return $this->getMockBuilder(CheckQuota::class)
			->setConstructorArgs([
				$this->appConfig,
				$this->config,
				$this->logger,
				$this->mailer,
				$this->l10nFactory,
				$this->userManager,
				$this->jobList,
				$this->notificationManager,
			])
			->onlyMethods($methods)
			->getMock();
	}

	protected function getNotificationMock(): INotification&MockObject {
		$notification = $this->createMock(INotification::class);
		$notification->method('setApp')->willReturnSelf();
		$notification->method('setObject')->willReturnSelf();
		$notification->method('setUser')->willReturnSelf();
		$notification->method('setDateTime')->willReturnSelf();
		$notification->method('setSubject')->willReturnSelf();
		return $notification;
	}

	public function testCheckRemovesJobOfDeletedUser(): void {
		$checkQuota = $this->getCheckQuota();

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('deleted')
			->willReturn(false);

		$this->jobList->expects($this->once())
			->method('remove')
			->with(User::class, ['uid' => 'deleted']);

		$checkQuota->expects($this->never())
			->method('getRelativeQuotaUsage');
		$this->notificationManager->expects($this->never())
			->method('notify');
		$this->mailer->expects($this->never())
			->method('send');

		$checkQuota->check('deleted');
	}

	public function testCheckClearsWarningWhenBelowThreshold(): void {
		$checkQuota = $this->getCheckQuota();

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('user')
			->willReturn(true);

		$checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn(42.0);

		$this->appConfig->expects($this->any())
			->method('getAppValueInt')
			->willReturnArgument(1);

		$this->notificationManager->expects($this->once())
			->method('createNotification')
			->willReturn($this->getNotificationMock());
		$this->notificationManager->expects($this->once())
			->method('markProcessed');
		$this->notificationManager->expects($this->never())
			->method('notify');
		$this->mailer->expects($this->never())
			->method('send');

		$checkQuota->check('user');
	}

	public function testCheckWarnsUser(): void {
		$checkQuota = $this->getCheckQuota();

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('user')
			->willReturn(true);
		$this->userManager->expects($this->never())
			->method('get');

		$checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn(97.0);

		$this->appConfig->expects($this->any())
			->method('getAppValueInt')
			->willReturnArgument(1);
		$this->appConfig->expects($this->any())
			->method('getAppValueBool')
			->willReturn(false);
		$this->config->expects($this->any())
			->method('getUserValue')
			->willReturn('');

		$this->notificationManager->expects($this->exactly(2))
			->method('createNotification')
			->willReturn($this->getNotificationMock());
		$this->notificationManager->expects($this->once())
			->method('notify');
		$this->mailer->expects($this->never())
			->method('send');

		$checkQuota->check('user');
	}

	public function testCheckSendsNoEmailToDisabledUser(): void {
		$checkQuota = $this->getCheckQuota();

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('isEnabled')
			->willReturn(false);
		$user->expects($this->never())
			->method('getEMailAddress');

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('user')
			->willReturn(true);
		$this->userManager->expects($this->once())
			->method('get')
			->with('user')
			->willReturn($user);

		$checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn(97.0);

		$this->appConfig->expects($this->any())
			->method('getAppValueInt')
			->willReturnArgument(1);
		$this->appConfig->expects($this->any())
			->method('getAppValueBool')
			->willReturn(true);
		$this->config->expects($this->any())
			->method('getUserValue')
			->willReturn('');

		// The notification is still issued, so it is cleared again
		// once the usage drops while the user is disabled
		$this->notificationManager->expects($this->exactly(2))
			->method('createNotification')
			->willReturn($this->getNotificationMock());
		$this->notificationManager->expects($this->once())
			->method('notify');

		$this->mailer->expects($this->never())
			->method('createEMailTemplate');
		$this->mailer->expects($this->never())
			->method('send');

		$checkQuota->check('user');
	}

	public function testCheckSendsEmailToEnabledUser(): void {
		$checkQuota = $this->getCheckQuota();

		$user = $this->createMock(IUser::class);
		$user->expects($this->once())
			->method('isEnabled')
			->willReturn(true);
		$user->expects($this->any())
			->method('getEMailAddress')
			->willReturn('user@example.com');
		$user->expects($this->any())
			->method('getUID')
			->willReturn('user');

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('user')
			->willReturn(true);
		$this->userManager->expects($this->once())
			->method('get')
			->with('user')
			->willReturn($user);

		$checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn(97.0);

		$this->appConfig->expects($this->any())
			->method('getAppValueInt')
			->willReturnArgument(1);
		$this->appConfig->expects($this->any())
			->method('getAppValueBool')
			->willReturn(true);
		$this->appConfig->expects($this->any())
			->method('getAppValueString')
			->willReturn('');
		$this->config->expects($this->any())
			->method('getUserValue')
			->willReturn('');

		$l = $this->createMock(IL10N::class);
		$l->expects($this->any())
			->method('t')
			->willReturnCallback(static function (string $string, array $args = []): string {
				return vsprintf($string, $args);
			});
		$this->l10nFactory->expects($this->any())
			->method('get')
			->willReturn($l);

		$this->notificationManager->expects($this->exactly(2))
			->method('createNotification')
			->willReturn($this->getNotificationMock());
		$this->notificationManager->expects($this->once())
			->method('notify');

		$emailTemplate = $this->createMock(IEMailTemplate::class);
		$this->mailer->expects($this->once())
			->method('createEMailTemplate')
			->willReturn($emailTemplate);

		$message = $this->createMock(IMessage::class);
		$message->expects($this->once())
			->method('setTo')
			->with(['user@example.com' => 'user'])
			->willReturnSelf();
		$this->mailer->expects($this->once())
			->method('createMessage')
			->willReturn($message);
		$this->mailer->expects($this->once())
			->method('send')
			->with($message);

		$checkQuota->check('user');
	}

	public static function dataGetRelativeQuotaUsage(): array {
		return [
			'unlimited quota' => [
				['quota' => FileInfo::SPACE_UNLIMITED, 'used' => 123 * 1024 ** 2, 'relative' => 12.3],
				0.0,
			],
			'quota below 5 MB' => [
				['quota' => 1024 ** 2, 'used' => 512 * 1024, 'relative' => 50.0],
				0.0,
			],
			'normal usage' => [
				['quota' => 100 * 1024 ** 2, 'used' => 87 * 1024 ** 2, 'relative' => 87.0],
				87.0,
			],
			// Server reports 100% for all users when the disk is full,
			// because the total space is capped by the free space
			'full disk but barely used quota' => [
				['quota' => 10 * 1024 ** 3, 'used' => 100 * 1024 ** 2, 'relative' => 100.0],
				0.98,
			],
			'over quota' => [
				['quota' => 1024 ** 3, 'used' => 1280 * 1024 ** 2, 'relative' => 100.0],
				125.0,
			],
		];
	}

	#[DataProvider(methodName: 'dataGetRelativeQuotaUsage')]
	public function testGetRelativeQuotaUsage(array $storageInfo, float $expected): void {
		$checkQuota = $this->getCheckQuota(['getStorageInfo']);

		$checkQuota->expects($this->once())
			->method('getStorageInfo')
			->with('user')
			->willReturn($storageInfo);

		$this->assertSame($expected, $checkQuota->getRelativeQuotaUsage('user'));
	}

	public function testGetRelativeQuotaUsageWithoutStorage(): void {
		$checkQuota = $this->getCheckQuota(['getStorageInfo']);

		$checkQuota->expects($this->once())
			->method('getStorageInfo')
			->with('user')
			->willThrowException(new NotFoundException());

		$this->assertSame(0.0, $checkQuota->getRelativeQuotaUsage('user'));
	}
}
