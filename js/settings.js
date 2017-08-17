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


(function(OC, OCA, _) {
	OCA.QuotaWarning = {
		timeouts: {
			'info_percentage': undefined,
			'warning_percentage': undefined,
			'alert_percentage': undefined
		},

		init: function() {
			$('.percentage-option > input').on('change keyup', function() {
				var $el = $(this);
				OCA.QuotaWarning._saveInput($el);
			});

			$('.email-option > input').on('change', function() {
				var $el = $(this);
				OCA.QuotaWarning._saveCheckbox($el);
			});
		},

		_saveInput: function($el) {
			var field = $el.attr('id').substring(14);
			if (!_.isUndefined(this.timeouts[field])) {
				clearTimeout(this.timeouts[field]);
			}

			this.timeouts[field] = setTimeout(_.bind(this._saveInputHandler, this, field, $el.val()), 750);
		},

		_saveInputHandler: function(field, value) {
			OC.msg.startAction('#quota_warning_feedback', t('quota_warning', 'Saving…'));
			OCP.AppConfig.setValue(
				'quota_warning',
				field,
				value,
				{
					success: function() {
						OC.msg.finishedSuccess('#quota_warning_feedback', t('quota_warning', 'Saved!'));
					}
				}
			);
		},

		_saveCheckbox: function($el) {
			OC.msg.startAction('#quota_warning_feedback', t('quota_warning', 'Saving…'));
			OCP.AppConfig.setValue(
				'quota_warning',
				$el.attr('id').substring(14),
				$el.attr('checked') ? 'yes' : 'no',
				{
					success: function() {
						OC.msg.finishedSuccess('#quota_warning_feedback', t('quota_warning', 'Saved!'));
					}
				}
			);
		}
	};
})(OC, OCA, _);

$(document).ready(function(){
	OCA.QuotaWarning.init();
});
