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
use OCA\QuotaWarning\CheckQuota;
use OCA\QuotaWarning\Notification\Notifier;
use OCP\IConfig;
use OCP\IL10N;
use OCP\L10N\IFactory;
use OCP\Notification\IManager;
use Test\TestCase;

/**
 * Class AppTest
 *
 * @package OCA\QuotaWarning\Tests
 * @group DB
 */
class AppTest extends TestCase {
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $language;
	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $languageFactory;
	/** @var IManager|\PHPUnit_Framework_MockObject_MockObject */
	protected $notificationManager;

	protected function setUp() {
		parent::setUp();

		$this->languageFactory = $this->createMock(IFactory::class);
		$this->notificationManager = $this->createMock(IManager::class);
		$this->language = $this->createMock(IL10N::class);
		$this->language->expects($this->any())
			->method('t')
			->willReturnCallback(function($string, $args) {
				return vsprintf($string, $args);
			});

		$this->overwriteService('NotificationManager', $this->notificationManager);
		$this->overwriteService('L10NFactory', $this->languageFactory);
	}

	protected function tearDown() {
		$this->restoreService('L10NFactory');
		$this->restoreService('NotificationManager');

		parent::tearDown();
	}

	public function testAppNotification() {
		$this->notificationManager->expects($this->once())
			->method('registerNotifierService')
			->with(Notifier::class);

		include __DIR__ . '/../../appinfo/app.php';
	}
}
