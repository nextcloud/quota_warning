<?php
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
