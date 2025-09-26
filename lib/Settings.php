<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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

	#[\Override]
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

	#[\Override]
	public function getSection(): string {
		return 'additional';
	}

	#[\Override]
	public function getPriority(): int {
		return 50;
	}
}
