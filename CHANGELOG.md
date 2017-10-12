# Changelog
All notable changes to this project will be documented in this file.

## 1.1.1 – 2017-10-13
### Fixed
 - Delete the background job when the user does not exist anymore
  [nextcloud/server#6803](https://github.com/nextcloud/server/issues/6803)
 - Add the user id to the email template for better styling
  [#23](https://github.com/nextcloud/quota_warning/pull/23)

## 1.1.0 – 2017-08-29
### Added
 - Allow to send an email in addition to the notification
  [#16](https://github.com/nextcloud/quota_warning/pull/16)
 - Allow to have an upsell info link in the notification and email
  [#16](https://github.com/nextcloud/quota_warning/pull/16)
 - Allow custom values for the percentages
  [#9](https://github.com/nextcloud/quota_warning/issues/9)

### Changed
 - Percentages have been adjusted to 85%, 90% and 95%
  [#16](https://github.com/nextcloud/quota_warning/pull/16)

## 1.0.1 – 2017-07-03

### Changed
 - Use quota icon instead of default app icon
  [#1](https://github.com/nextcloud/quota_warning/pull/1)

### Fixed
 - App not working for users that are created after app installation [#10](https://github.com/nextcloud/quota_warning/issues/10)

## 1.0.0 – 2017-05-29
### Added
 - Initial release with notifications
