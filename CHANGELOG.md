# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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