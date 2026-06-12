<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Tests;

use OCA\QuotaWarning\CheckQuota;
use OCP\AppFramework\Services\IAppConfig;
use OCP\BackgroundJob\IJobList;
use OCP\Files\FileInfo;
use OCP\Files\NotFoundException;
use OCP\IConfig;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Notification\IManager;
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

	protected function getCheckQuota(): CheckQuota&MockObject {
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
			->onlyMethods(['getStorageInfo'])
			->getMock();
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
		$checkQuota = $this->getCheckQuota();

		$checkQuota->expects($this->once())
			->method('getStorageInfo')
			->with('user')
			->willReturn($storageInfo);

		$this->assertSame($expected, $checkQuota->getRelativeQuotaUsage('user'));
	}

	public function testGetRelativeQuotaUsageWithoutStorage(): void {
		$checkQuota = $this->getCheckQuota();

		$checkQuota->expects($this->once())
			->method('getStorageInfo')
			->with('user')
			->willThrowException(new NotFoundException());

		$this->assertSame(0.0, $checkQuota->getRelativeQuotaUsage('user'));
	}
}
