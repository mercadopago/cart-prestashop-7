# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.7.1] - 2021-10-06

### Fixed
- Fixed payment methods ATM Mexico

### Changed
- Changed the structure of fields sent to Mercado Pago

## [4.7.0] - 2021-08-09

### Fixed
- Fixed create mpmodule table version on plugin update

### Changed
- Reversed the credential configuration order

### Removed
- Removed meliplaces, pix, account money payments from Ticket and Checkout Pro (These payments don't work yet)

## [4.6.1] - 2021-06-08

### Fixed
- Check total order price x mercado pago total price and add item with difference

## [4.6.0] - 2021-05-21

### Added
- Added source_news to receive only one type of notification
- Added plugin version to logs on install hook
- Added new features to improve security
- Added payment fraud status and rules to update orders
- Added rule on notification to update order payment transaction total

### Removed
- Removed cart_id as query param to improve security
- Removed unused evaluation modal

### Fixed
- Fixed modal javascript on Checkout Pro for PS 1.6
- Fixed cart total on CustomCheckout to show correct values with automatic cart rules
- Fixed disableFinishOrderButton method on custom card JS

## [4.5.1] - 2021-03-05

### Added
- Improved security on admin forms

### Changed
- Changed notification status responses to avoid unnecessary mercadopago notifications

### Removed
- Removed log files with PII data

## [4.5.0] - 2021-02-17

### Changed
- Migrated logs from plugin file to Prestashop Logger

## [4.4.4] - 2021-01-27

### Fixed
- Verify order amount vs paid amount when is approved status notification

## [4.4.3] - 2021-01-18

### Fixed
- Added the prefix in the upgrade table

## [4.4.2] - 2021-01-18

### Fixed
- Check paid amount before change order status
- Set amount from Mercado Pago response on order_payments

## [4.4.1] - 2021-01-18

### Fixed
- Remove visibility from const to be compatible with PHP7.0

## [4.4.0] - 2020-12-28

### Added
- Added admin tab to view or download the plugin log
- Added plugin version o notification response

### Changed
- Renamed from Checkout Mercado Pago for Checkout Pro

### Fixed
- Fixed getIssuers method on custom-card.js

## [4.3.0] - 2020-11-10

### Added
- Improved security (added access token in the header for all calls to Mercado Livre and Mercado Pago endpoints)
- Added new endpoint to validate Access Token to substitute old validation process
- Added logs by plugin version
- Added try catch in the updateTransactionId method
- Added more logs in the notification methods
- Added validation to verify that the cart total is greater than zero in the validateOrderState method
- Added logs for database failures

### Fixed
- Fixed homologation flow

## [4.2.0] - 2020-09-25

### Added
- Refactor update order status
- Add rule to validate backorder status
- JS files versioning
- JS files minification
- CSS files versioning
- CSS files minification
- Code Standards for JS files
- Code Standards for CSS files
- Code Standards for PHP files

### Fixed
- Fix splitted orders update
- Fix getConditionAndTerms on custom checkout validations
- Fix getNotificationResponse to static and avoid unnecessary request to MP Payments API

### Changed
- Move checkouts classes from /model to /checkouts
- Create new models for order_state, order_state_lang, cart_rule and cart_rule_rule

## [4.1.1] - 2020-06-03

### Added
- We added status map for Notification

### Fixed
- We fixed statement_descriptor: from null to ''
- We fixed document number mask
- We fixed notification update order flow
- We fixed the problem after removing Mercado Envíos

### Removed
- We removed Mercado Envíos default configurations

## [4.1.0] - 2020-03-25

### Break Change
- In this version you must paste the public_key of sandbox and production to be able to sell. Before updating the plugin, activate the maintenance mode and do some tests to check that nothing breaks

### Added
- New translations for Chile and Uruguay.
- Custom Checkout with:
 - Binary mode
 - Discount for paying with Mercado Pago.
- Ticket Checkout with:
 - Select available payment methods.
 - Choose the expiration date.
 - Discount for paying with Mercado Pago.

### Fixed
- We fixed the mobile layout of the Mercado Pago Checkout.
- We fixed the creation of the order, allowing the function to recover the value of the customer_secure_key.
- Mobile layout

### Changed
- Now the Mercado Pago Checkout works through a modal: your customers can complete the purchase without leaving the site.
- We renew the plugin settings screen.
- We renew the plugin code structure.
- We merged the plugin versions for PS1.6 and PS1.7.

### Removed
- Mercado Envíos.
- Discount coupons.
- Installment calculator.
