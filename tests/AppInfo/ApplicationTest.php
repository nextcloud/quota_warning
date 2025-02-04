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
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\TimedJob;
use OCP\Migration\IRepairStep;
use OCP\Notification\INotifier;
use Test\TestCase;

/**
 * Class ApplicationTest
 *
 * @package OCA\QuotaWarning\Tests
 * @group DB
 */
class ApplicationTest extends TestCase {
	/** @var \OCA\QuotaWarning\AppInfo\Application */
	protected $app;

	/** @var \OCP\AppFramework\IAppContainer */
	protected $container;

	protected function setUp(): void {
		parent::setUp();
		$this->app = new Application();
		$this->container = $this->app->getContainer();
	}

	public function testContainerAppName(): void {
		$this->app = new Application();
		$this->assertEquals(Application::APP_ID, $this->container->getAppName());
	}

	public function dataContainerQuery(): array {
		return [
			[Application::class, App::class],
			[Notifier::class, INotifier::class],
			[Install::class, IRepairStep::class],
			[User::class, IJob::class],
			[User::class, TimedJob::class],
		];
	}

	/**
	 * @dataProvider dataContainerQuery
	 */
	public function testContainerQuery(string $service, string $expected): void {
		$this->assertInstanceOf($expected, $this->container->query($service));
	}
}
