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

use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\Settings\ISettings;

class Settings implements ISettings {

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function getForm(): TemplateResponse {
		$response = new TemplateResponse('quota_warning', 'settings');
		$response->setParams([
			'info_percentage' => $this->config->getAppValue('quota_warning', 'info_percentage', '85'),
			'info_email' => $this->config->getAppValue('quota_warning', 'info_email', 'no') === 'yes',
			'warning_percentage' => $this->config->getAppValue('quota_warning', 'warning_percentage', '90'),
			'warning_email' => $this->config->getAppValue('quota_warning', 'warning_email', 'no') === 'yes',
			'alert_percentage' => $this->config->getAppValue('quota_warning', 'alert_percentage', '95'),
			'alert_email' => $this->config->getAppValue('quota_warning', 'alert_email', 'no') === 'yes',
			'plan_management_url' => $this->config->getAppValue('quota_warning', 'plan_management_url'),
			'repeat_warning' => $this->config->getAppValue('quota_warning', 'repeat_warning', '7'),
		]);

		return $response;
	}

	public function getSection(): string {
		return 'additional';
	}

	public function getPriority(): int {
		return 50;
	}
}
