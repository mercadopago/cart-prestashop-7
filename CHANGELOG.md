# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [4.17.3] - 2025-02-03
### Fixed
- Fixed order status update due to error in value rounding

## [4.17.2] - 2024-04-03
### Fixed
- Update `mp-plugins/php-sdk` version to 2.10.1

## [4.17.1] - 2024-04-03
### Fixed
- Import SDK files with vendor without using `composer install`

## [4.17.0] - 2024-04-01

### Added
- Visualization of installments with interest on the order confirmation screen for the buyer
- New payment method: PSE (Only Colombia)

### Changed
- Using `mp-plugins/php-sdk` to make payment calls to the Mercado Pago API

## [4.16.0] - 2024-05-02

### Changed
- Adjusting texts on the configuration screen

## [4.15.0] - 2023-26-12

### Changed
- Changes the endpoints that the plugin calls to create payment, preferences and obtaining payment-methods.
- Changed endpoints: 
    - `v1/payments` to `/ppcore/prod/transaction/v1/payments`
    - `checkout/preferences` to `/ppcore/prod/transaction/v1/preferences`
    - `v1/bifrost/payment-methods` to `/ppcore/prod/payment-methods/v1/payment-methods`
    
### Fixed
- Updated copyright to 2023
- Added validation to ensure that PHP files are executed in the PrestaShop context
- Added .htaccess file in the root folder, to prevent someone from listing the files of the module, and direct execution of PHP file

## [4.12.0] - 2022-16-11

### Changed
- Changed endpoint from v1/payment_methods to v1/bifrost/payment-methods

### Removed
- Removed function used as mock to add payment places related to paycash

### Improved
- Improved js selector that gets button element, finish order 

## [4.11.3] - 2022-30-09

### Fixed
- Fixed php notice on order creation
- Error log changed to information log in notifications
- Removed tags used for translation and adapted related HTML tags
- Changed Json encode/decode functions from Tools to native functions
- Updated Tools::redirectLink to Tools::redirect
- Added validation for null, empty or invalid merchant_order_id

## [4.11.2] - 2022-16-08

### Fixed
- PSE return page

## [4.11.1] - 2022-07-07

### Fixed
- Sanitize id's from get methods 

## [4.11.0] - 2022-07-04

### Added
- validateOrder or createOrder with API Data.

### Updated
- Same templates sanitized by javascript method
- Use in query parameters pSQL e bqSQL functions

### Fixed
- Customer log messages

## [4.10.1] - 2022-04-25

### Added
- Added compatibility with Mercado Pago discounts

### Updated
- Updated npm packages
- Updated composer packages

### Fixed
- Fixed validateOrder on createOrder method using cart total instead of payments total sum
- Fixed Wallet Button discount value to avoid fraud status
- Fixed round to MLC (Chile) and MCO (Colombia) on notification getTotal method
- Fixed checkout ticket validation to MLU (Uruguay)

## [4.10.0] - 2022-03-21

### Added
- Added wallet button to checkout custom
- Added security code validation to checkout custom
- Added deprecation banner for version 1.6

### Changed
- Migrated SDK JS from v1 to v2
- Adjusted logos from checkouts
- Adjusted translations

### Fixed
- Translations

## [4.9.1] - 2022-02-08

### Fixed
- Fixed quotes in translation strings

## [4.9.0] - 2022-02-07

### Added
- Added paycash as a payment method for Mexico
- Added Pix as a new payment method for Brazil

## [4.8.1] - 2022-01-11

### Fixed
- Updated copyright to 2022
- Updated Mercado Pago's images
- Fixed ps_versions_compliancy variable order

## [4.8.0] - 2021-10-25

### Added
- Added device fingerprint at checkout

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
