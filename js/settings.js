/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
				$el.prop('checked') ? 'yes' : 'no',
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
