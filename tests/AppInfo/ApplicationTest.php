<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning\Tests\AppInfo;

use OCA\QuotaWarning\AppInfo\Application;
use OCA\QuotaWarning\Job\User;
use OCA\QuotaWarning\Migration\Install;
use OCA\QuotaWarning\Notification\Notifier;
use OCP\AppFramework\App;
use OCP\AppFramework\IAppContainer;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\Migration\IRepairStep;
use OCP\Notification\INotifier;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\TestCase;

class ApplicationTest extends TestCase {
	protected Application $app;
	protected IAppContainer $container;

	protected function setUp(): void {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName(): void {
		$this->assertEquals(Application::APP_ID, $this->container->getAppName());
	}

	public static function dataContainerQuery(): array {
		return [
			[Application::class, App::class],
			[Notifier::class, INotifier::class],
			[Install::class, IRepairStep::class],
			[User::class, IJob::class],
			[User::class, TimedJob::class],
		];
	}

	#[DataProvider('dataContainerQuery')]
	public function testContainerQuery(string $service, string $expected): void {
		$this->assertInstanceOf($expected, $this->container->query($service));
	}
}
