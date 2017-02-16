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

namespace OCA\QuotaWarning\Tests\Notification;

use OCA\QuotaWarning\AppInfo\Application;
use OCA\QuotaWarning\CheckQuota;
use OCA\QuotaWarning\Notification\Notifier;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;

class NotifierTest extends \Test\TestCase {
	/** @var Notifier */
	protected $notifier;

	/** @var IFactory|\PHPUnit_Framework_MockObject_MockObject */
	protected $factory;
	/** @var CheckQuota|\PHPUnit_Framework_MockObject_MockObject */
	protected $checkQuota;
	/** @var IURLGenerator|\PHPUnit_Framework_MockObject_MockObject */
	protected $urlGenerator;
	/** @var IL10N|\PHPUnit_Framework_MockObject_MockObject */
	protected $l;

	protected function setUp() {
		parent::setUp();

		$this->checkQuota = $this->createMock(CheckQuota::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function($string, $args) {
				return vsprintf($string, $args);
			});
		$this->factory = $this->createMock(IFactory::class);
		$this->factory->expects($this->any())
			->method('get')
			->willReturn($this->l);

		$this->notifier = new Notifier(
			$this->checkQuota,
			$this->factory,
			$this->urlGenerator
		);
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Unknown app
	 */
	public function testPrepareWrongApp() {
		/** @var INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn('notifications');
		$notification->expects($this->never())
			->method('getSubject');

		$this->notifier->prepare($notification, 'en');
	}

	/**
	 * @expectedException \InvalidArgumentException
	 * @expectedExceptionMessage Less usage
	 */
	public function testPrepareLessUsage() {
		/** @var INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getUser')
			->willReturn('user');
		$notification->expects($this->never())
			->method('getSubjectParameters');

		$this->checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn(49.0);

		$this->notifier->prepare($notification, 'en');
	}

	public function dataPrepare() {
		return [
			[50.1, ' 50% '],
			[70.6, ' 71% '],
			[102.3, ' 100% '],
		];
	}

	/**
	 * @dataProvider dataPrepare
	 *
	 * @param float $quota
	 * @param string $stringContains
	 */
	public function testPrepare($quota, $stringContains) {
		$this->checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn($quota);

		/** @var INotification|\PHPUnit_Framework_MockObject_MockObject $notification */
		$notification = $this->createMock(INotification::class);
		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getUser')
			->willReturn('user');
		$notification->expects($this->once())
			->method('getSubjectParameters')
			->willReturn(['usage' => $quota]);
		$notification->expects($this->once())
			->method('setParsedSubject')
			->with($this->stringContains($stringContains))
			->willReturnSelf();

		$this->urlGenerator->expects($this->once())
			->method('imagePath')
			->with(Application::APP_ID, 'app-dark.svg')
			->willReturn('icon-url');
		$this->urlGenerator->expects($this->once())
			->method('getAbsoluteURL')
			->with('icon-url')
			->willReturn('absolute-icon-url');
		$notification->expects($this->once())
			->method('setIcon')
			->with('absolute-icon-url')
			->willReturnSelf();

		$return = $this->notifier->prepare($notification, 'en');

		$this->assertEquals($notification, $return);
	}
}
