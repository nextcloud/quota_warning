<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

/** @var array $_ */
/** @var \OCP\IL10N $l */
script('quota_warning', 'settings');
style('quota_warning', 'settings');
?>

<div id="quota-warning" class="section">
	<h2 class="inlineblock">
		<?php p($l->t('Quota warning')); ?>
	</h2>
	<span id="quota_warning_feedback" class="msg"></span>

	<p class="percentage-option">
		<label for="quota_warning_info_percentage"><?php p($l->t('First notification')) ?></label>
		<input id="quota_warning_info_percentage" type="number" min="0" max="100" value="<?php p($_['info_percentage']) ?>" />
	</p>
	<p class="email-option">
		<input type="checkbox" name="quota_warning_info_email" id="quota_warning_info_email" class="checkbox"
			   value="1" <?php if ($_['info_email']) {
			   	print_unescaped('checked="checked"');
			   } ?> />
		<label for="quota_warning_info_email"><?php p($l->t('Send an email'));?></label>
	</p>

	<p class="percentage-option">
		<label for="quota_warning_warning_percentage"><?php p($l->t('Second notification')) ?></label>
		<input id="quota_warning_warning_percentage" type="number" min="0" max="100" value="<?php p($_['warning_percentage']) ?>" />
	</p>
	<p class="email-option">
		<input type="checkbox" name="quota_warning_warning_email" id="quota_warning_warning_email" class="checkbox"
			   value="1" <?php if ($_['warning_email']) {
			   	print_unescaped('checked="checked"');
			   } ?> />
		<label for="quota_warning_warning_email"><?php p($l->t('Send an email'));?></label>
	</p>

	<p class="percentage-option">
		<label for="quota_warning_alert_percentage"><?php p($l->t('Final notification')) ?></label>
		<input id="quota_warning_alert_percentage" type="number" min="0" max="100" value="<?php p($_['alert_percentage']) ?>" />
	</p>
	<p class="email-option">
		<input type="checkbox" name="quota_warning_alert_email" id="quota_warning_alert_email" class="checkbox"
			   value="1" <?php if ($_['alert_email']) {
			   	print_unescaped('checked="checked"');
			   } ?> />
		<label for="quota_warning_alert_email"><?php p($l->t('Send an email'));?></label>
	</p>

	<p class="percentage-option">
		<label for="quota_warning_plan_management_url"><?php p($l->t('Link to quota management')) ?></label>
		<input id="quota_warning_plan_management_url" value="<?php p($_['plan_management_url']) ?>" placeholder="https://…" />
	</p>

	<p class="percentage-option">
		<label for="quota_warning_repeat_warning"><?php p($l->t('Resend notifications after … days')) ?></label>
		<input id="quota_warning_repeat_warning" type="number" min="0" value="<?php p($_['repeat_warning']) ?>" />
		<em><?php p($l->t('Set to 0 if the user should only receive one notification.')) ?></em>
	</p>
</div>
