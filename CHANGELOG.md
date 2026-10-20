# Changelog

All notable changes to this project are documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and this project adheres to
[Semantic Versioning](https://semver.org/spec/v2.0.0.html). Until 1.0.0, minor versions may contain breaking
changes.

## [Unreleased]

## [0.4.0] - 2026-10-20

### Changed

- The plugin moves to Atriametal: the package is now `atriametal/sylius-spam-protection-plugin`, and its namespace
  `Atriametal\SyliusSpamProtectionPlugin`. Replace `florentingarnier/sylius-spam-protection-plugin` in your `composer.json` and
  `FlorentinGarnier\SyliusSpamProtectionPlugin\FlorentinGarnierSyliusSpamProtectionPlugin` in your
  `config/bundles.php` with `Atriametal\SyliusSpamProtectionPlugin\AtriametalSyliusSpamProtectionPlugin`.
- The blocks of the template events and Twig hooks are renamed `atriametal_spam_protection` and
  `atriametal_spam_protection_error`: rename them where your configuration disables or moves them.
- The template namespace is now `@AtriametalSyliusSpamProtectionPlugin`.

## [0.3.1] - 2026-10-08

### Changed

- Requires florentingarnier/spam-protection-bundle 0.2.2, which translates the rejection message: the visitor
  of a theme rendering the errors of the form saw its translation key.

### Fixed

- The rejection message rendered by the plugin is recognized by its message template, which remains the
  translation key once the message is translated.

## [0.3.0] - 2026-10-07

### Added

- Support for Sylius 2: the protection is rendered through the shop Twig hooks. The re-renders of the
  registration Live Component are not verified, so they neither consume the token nor count as rejected
  attempts, and they keep the token issued with the page, which would otherwise be too young when the visitor
  submits the form right after the last field.

### Changed

- Requires florentingarnier/spam-protection-bundle 0.2.1, whose JavaScript solver no longer leaves some forms
  unsent in Chrome.
- The plugin requires the Symfony components it uses directly, in the versions supported by both Sylius and
  florentingarnier/spam-protection-bundle: 5.4, 6.4, 7.4 or 8. The CI tests the PHP and Symfony versions that
  Sylius tests for each of its versions.
- Development follows the Sylius plugin skeleton: integration tests on the Sylius Test Application, Behat
  scenarios in Chrome and without JavaScript, Sylius coding standard (ECS) and PHPStan at the maximum level, run
  in CI against the supported Sylius versions.

### Fixed

- A rejected visitor is now told so: the Sylius shop does not render the errors of the contact, registration
  and password reset forms, so the plugin renders its rejection message at the top of these forms.

## [0.2.1] - 2026-10-06

### Fixed

- A theme that renders `form.spam_protection` itself before calling the form template event no longer fails
  with "Field "spam_protection" has already been rendered": the plugin skips a field that is already rendered.

## [0.2.0] - 2026-10-06

### Removed

- Support for Sylius 1.12 and 1.13. They are no longer maintained, and their dependencies have security
  advisories that will never be fixed. Version 0.1 of the plugin remains available for them.

## [0.1.0] - 2026-10-06

### Added

- Initial release: protects the Sylius shop contact, registration and password reset request forms, rendered
  through the shop template events. Supports Sylius 1.12 to 1.14 and requires
  florentingarnier/spam-protection-bundle 0.2.

[Unreleased]: https://github.com/atriametal/sylius-spam-protection-plugin/compare/v0.4.0...HEAD
[0.4.0]: https://github.com/atriametal/sylius-spam-protection-plugin/compare/v0.3.1...v0.4.0
[0.3.1]: https://github.com/atriametal/sylius-spam-protection-plugin/compare/v0.3.0...v0.3.1
[0.3.0]: https://github.com/atriametal/sylius-spam-protection-plugin/compare/v0.2.1...v0.3.0
[0.2.1]: https://github.com/atriametal/sylius-spam-protection-plugin/compare/v0.2.0...v0.2.1
[0.2.0]: https://github.com/atriametal/sylius-spam-protection-plugin/compare/v0.1.0...v0.2.0
[0.1.0]: https://github.com/atriametal/sylius-spam-protection-plugin/releases/tag/v0.1.0
