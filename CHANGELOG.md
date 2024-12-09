# Changelog
All notable changes to this project will be documented in this file.

## 1.21.0 – 202Y-MM-DD
### Changed
- Require Nextcloud 29

## 1.20.0 – 2024-08-26
### Changed
- Nextcloud 30 compatibility
- Require Nextcloud 28

## 1.19.0 – 2024-03-29
### Changed
- Nextcloud 29 compatibility
- Require Nextcloud 27

## 1.18.0 – 2023-12-01
### Changed
- Nextcloud 28 compatibility
- Require Nextcloud 26

## 1.17.0 – 2023-05-15
### Changed
- Nextcloud 27 compatibility
- Require Nextcloud 25

## 1.16.0 – 2023-03-27
### Changed
- Nextcloud 26 compatibility
- Require Nextcloud 24

## 1.15.0 – 2022-10-25
### Changed
- Nextcloud 25 compatibility
- Require Nextcloud 23

## 1.14.0 – 2022-04-11
### Changed
- Nextcloud 24 compatibility

## 1.13.1 – 2022-02-23
### Fixed
- Mark background job as time insensitive

## 1.13.0 – 2021-12-02
### Changed
- Nextcloud 23 compatibility

## 1.11.0 – 2021-07-21
### Changed
- Nextcloud 22 compatibility

### Fixed
- Fix "Send an email" checkbox in admin settings
  [#66](https://github.com/nextcloud/quota_warning/pull/66)

## 1.10.0 – 2021-01-08
### Changed
- Nextcloud 21 compatibility

## 1.9.1 – 2020-10-13
### Fixed
- Fix type error when checking user quota
  [#57](https://github.com/nextcloud/quota_warning/pull/57)

## 1.9.0 – 2020-08-28
### Changed
- Nextcloud 20 compatibility

## 1.8.0 – 2020-06-03
### Changed
- Nextcloud 19 compatibility

## 1.7.0 – 2020-01-17
### Changed
- Nextcloud 18 compatibility

## 1.6.0 – 2019-09-03
### Changed
- Nextcloud 17 compatibility

## 1.5.0 – 2019-04-01
### Changed
- Nextcloud 16 compatibility

## 1.4.0 – 2018-12-04
### Changed
- Nextcloud 15 compatibility

## 1.3.0 – 2018-08-23
### Changed
- Nextcloud 14 compatibility

## 1.2.0 – 2017-11-20
### Changed
- Nextcloud 13 compatibility

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
