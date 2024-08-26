<?php

declare(strict_types=1);
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
use OCP\IConfig;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\L10N\IFactory;
use OCP\Notification\INotification;
use PHPUnit\Framework\MockObject\MockObject;

class NotifierTest extends \Test\TestCase {
	/** @var Notifier */
	protected $notifier;

	/** @var IFactory|MockObject */
	protected $factory;
	/** @var CheckQuota|MockObject */
	protected $checkQuota;
	/** @var IConfig|MockObject */
	protected $config;
	/** @var IURLGenerator|MockObject */
	protected $urlGenerator;
	/** @var IL10N|MockObject */
	protected $l;

	protected function setUp(): void {
		parent::setUp();

		$this->checkQuota = $this->createMock(CheckQuota::class);
		$this->config = $this->createMock(IConfig::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->l = $this->createMock(IL10N::class);
		$this->l->expects($this->any())
			->method('t')
			->willReturnCallback(function ($string, $args) {
				return vsprintf($string, $args);
			});
		$this->factory = $this->createMock(IFactory::class);
		$this->factory->expects($this->any())
			->method('get')
			->willReturn($this->l);

		$this->notifier = new Notifier(
			$this->checkQuota,
			$this->config,
			$this->factory,
			$this->urlGenerator
		);
	}

	public function testPrepareWrongApp(): void {
		/** @var INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn('notifications');
		$notification->expects($this->never())
			->method('getSubject');

		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unknown app');
		$this->notifier->prepare($notification, 'en');
	}

	public function testPrepareLessUsage(): void {
		/** @var INotification|MockObject $notification */
		$notification = $this->createMock(INotification::class);

		$notification->expects($this->once())
			->method('getApp')
			->willReturn(Application::APP_ID);
		$notification->expects($this->once())
			->method('getUser')
			->willReturn('user');
		$notification->expects($this->never())
			->method('getSubjectParameters');

		$this->config->expects($this->once())
			->method('getAppValue')
			->with('quota_warning', 'info_percentage', '85')
			->willReturnArgument(2);

		$this->checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn(84.0);

		$this->expectException(\OCP\Notification\AlreadyProcessedException::class);
		$this->notifier->prepare($notification, 'en');
	}

	public function dataPrepare(): array {
		return [
			[85.1, ' 85% ', 'app-dark.svg'],
			[94.9, ' 95% ', 'app-warning.svg'],
			[102.3, ' 100% ', 'app-alert.svg'],
		];
	}

	/**
	 * @dataProvider dataPrepare
	 */
	public function testPrepare(float $quota, string $stringContains, string $image): void {
		$this->checkQuota->expects($this->once())
			->method('getRelativeQuotaUsage')
			->with('user')
			->willReturn($quota);

		$this->config->expects($this->any())
			->method('getAppValue')
			->willReturnMap([
				['quota_warning', 'info_percentage', '85', '85'],
				['quota_warning', 'warning_percentage', '90', '90'],
				['quota_warning', 'alert_percentage', '95', '95'],
			]);

		/** @var INotification|MockObject $notification */
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
			->with(Application::APP_ID, $image)
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
