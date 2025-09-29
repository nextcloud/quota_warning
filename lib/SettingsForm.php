<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\QuotaWarning;

use OCP\AppFramework\Services\IAppConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\Settings\DeclarativeSettingsTypes;
use OCP\Settings\IDeclarativeSettingsFormWithHandlers;

class SettingsForm implements IDeclarativeSettingsFormWithHandlers {
	public function __construct(
		private readonly IAppConfig $appConfig,
		private readonly IL10N $l,
	) {
	}

	#[\Override]
	public function getSchema(): array {
		return [
			'id' => 'quota_warning',
			'priority' => 50,
			'section_type' => DeclarativeSettingsTypes::SECTION_TYPE_ADMIN,
			'section_id' => 'additional',
			'storage_type' => DeclarativeSettingsTypes::STORAGE_TYPE_EXTERNAL,
			'title' => $this->l->t('Quota warning'),
			'fields' => [
				[
					'id' => 'info_percentage',
					'title' => $this->l->t('First notification'),
					'type' => DeclarativeSettingsTypes::NUMBER,
					'default' => 85,
				],
				[
					'id' => 'info_email',
					'title' => '',
					'label' => $this->l->t('Send an email'),
					'type' => DeclarativeSettingsTypes::CHECKBOX,
					'default' => false,
				],
				[
					'id' => 'warning_percentage',
					'title' => $this->l->t('Second notification'),
					'type' => DeclarativeSettingsTypes::NUMBER,
					'default' => 90,
				],
				[
					'id' => 'warning_email',
					'title' => '',
					'label' => $this->l->t('Send an email'),
					'type' => DeclarativeSettingsTypes::CHECKBOX,
					'default' => false,
				],
				[
					'id' => 'alert_percentage',
					'title' => $this->l->t('Final notification'),
					'type' => DeclarativeSettingsTypes::NUMBER,
					'default' => 95,
				],
				[
					'id' => 'alert_email',
					'title' => '',
					'label' => $this->l->t('Send an email'),
					'type' => DeclarativeSettingsTypes::CHECKBOX,
					'default' => false,
				],
				[
					'id' => 'repeat_warning',
					'title' => $this->l->t('Resend notifications after … days'),
					'description' => $this->l->t('Set to 0 if the user should only receive one notification.'),
					'type' => DeclarativeSettingsTypes::NUMBER,
					'default' => 95,
				],
				[
					'id' => 'plan_management_url',
					'title' => $this->l->t('Link to quota management'),
					'type' => DeclarativeSettingsTypes::URL,
					'default' => '',
					'placeholder' => 'https://…'
				],
			]
		];
	}

	#[\Override]
	public function getValue(string $fieldId, IUser $user): mixed {
		if ($fieldId === 'info_percentage') {
			return $this->appConfig->getAppValueInt($fieldId, 85);
		}
		if ($fieldId === 'warning_percentage') {
			return $this->appConfig->getAppValueInt($fieldId, 90);
		}
		if ($fieldId === 'alert_percentage') {
			return $this->appConfig->getAppValueInt($fieldId, 95);
		}
		if ($fieldId === 'repeat_warning') {
			return $this->appConfig->getAppValueInt($fieldId, 7);
		}
		if (in_array($fieldId, [
			'info_email',
			'warning_email',
			'alert_email',
		], true)) {
			return $this->appConfig->getAppValueBool($fieldId);
		}
		if ($fieldId === 'plan_management_url') {
			return $this->appConfig->getAppValueString($fieldId);
		}
		return null;
	}

	#[\Override]
	public function setValue(string $fieldId, mixed $value, IUser $user): void {
		if (in_array($fieldId, [
			'info_percentage',
			'warning_percentage',
			'alert_percentage',
			'repeat_warning',
		], true)) {
			if ($value >= 0 && ($value <= 100 || $fieldId === 'repeat_warning')) {
				$this->appConfig->setAppValueInt($fieldId, (int)$value);
			}
		} elseif (in_array($fieldId, [
			'info_email',
			'warning_email',
			'alert_email',
		], true)) {
			$this->appConfig->setAppValueBool($fieldId, (bool)$value);
		} elseif ($fieldId === 'plan_management_url') {
			$this->appConfig->setAppValueString($fieldId, (string)$value);
		}

	}

}
