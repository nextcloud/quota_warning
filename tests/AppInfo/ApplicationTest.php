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

	public function testContainerAppName() {
		$this->app = new Application();
		$this->assertEquals(Application::APP_ID, $this->container->getAppName());
	}

	public function dataContainerQuery() {
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
	 * @param string $service
	 * @param string $expected
	 */
	public function testContainerQuery($service, $expected) {
		$this->assertInstanceOf($expected, $this->container->query($service));
	}
}
