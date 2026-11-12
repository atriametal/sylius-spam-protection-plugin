# Sylius Spam Protection Plugin

[![CI](https://github.com/atriametal/sylius-spam-protection-plugin/actions/workflows/ci.yml/badge.svg)](https://github.com/atriametal/sylius-spam-protection-plugin/actions/workflows/ci.yml)
[![Latest Version](https://img.shields.io/packagist/v/atriametal/sylius-spam-protection-plugin.svg)](https://packagist.org/packages/atriametal/sylius-spam-protection-plugin)
[![Total Downloads](https://img.shields.io/packagist/dt/atriametal/sylius-spam-protection-plugin.svg)](https://packagist.org/packages/atriametal/sylius-spam-protection-plugin)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)
![Sylius](https://img.shields.io/badge/sylius-1.14%20%7C%202.1%2B-1abb9c.svg)

Protects the public forms of your Sylius shop against spam bots, without reCAPTCHA and without any puzzle for
your customers. It relies on
[florentingarnier/spam-protection-bundle](https://github.com/FlorentinGarnier/spam-protection-bundle): a
honeypot, single-use timed tokens, a proof of work solved by the browser, per-form rate limiting, IP reputation,
gibberish detection and the rejection of links sent from datacenters, VPNs and Tor.

| Sylius form | Scope | Free text checked |
|-------------|-------|-------------------|
| Contact (`ContactType`) | `contact` | `message` |
| Registration (`CustomerRegistrationType`) | `registration` | — |
| Password reset request (`UserRequestPasswordResetType`) | `password_reset` | — |

## Upgrading from florentingarnier/sylius-spam-protection-plugin

The plugin moved to Atriametal in 0.4.0: require `atriametal/sylius-spam-protection-plugin` instead, and follow
the [CHANGELOG](CHANGELOG.md#040---2026-10-20) to rename the bundle class and the blocks. The previous package is
abandoned.

## Requirements

- Sylius 1.14, or 2.1 and later. The other versions are no longer supported by the plugin:
  - Sylius 1.12 and 1.13 are no longer maintained, and their dependencies have security advisories that will
    never be fixed. Use version 0.1 of the plugin with them.
  - Every release of Sylius 2.0 is affected by the security advisory
    [GHSA-cqw6-3fwg-cfv4](https://github.com/Sylius/Sylius/security/advisories/GHSA-cqw6-3fwg-cfv4), so Composer
    2.9 and later refuses to install it. Use version 0.4 of the plugin with it, or upgrade Sylius.
- PHP 8.2 or later, and Symfony 5.4, 6.4, 7.4 or 8, within the versions supported by your Sylius version (Symfony
  7.0 to 7.3 are not supported by [spam-protection-bundle](https://github.com/FlorentinGarnier/spam-protection-bundle)):

  | Sylius | Symfony | PHP |
  |--------|---------|-----|
  | 1.14 | 5.4, 6.4 | 8.2, 8.3 |
  | 2.1, 2.2 | 6.4, 7.4 | 8.3 to 8.5 |
  | 2.3 | 6.4, 7.4, 8 | 8.3 to 8.5 (8.4 or later with Symfony 8) |

  These are the combinations tested by Sylius itself, and by the CI of the plugin.
- `sylius/mailer-bundle` 2, to flag the suspicious contact requests. Sylius 2 requires it; Sylius 1.14 also
  accepts version 1.8, which the plugin no longer does.
- JavaScript in the customer's browser

Sylius 1.14 requires `api-platform/core` 2.7, whose releases are all affected by security advisories. Composer 2.9 and later refuses to install them unless your project ignores these advisories, as
this plugin does for its own CI in [composer.json](composer.json) (`config.policy.advisories.ignore-id`). This
concerns Sylius itself, not the plugin, which does not use API Platform.

### Sylius 2

The registration form of Sylius 2 is a Live Component, which submits the form every time it re-renders it, to
validate the fields. These re-renders save nothing, so the plugin does not verify them: otherwise they would
consume the token and count as rejected attempts. Live actions are still verified, as they may save the form.

## Installation

1. Require the plugin:

   ```bash
   composer require atriametal/sylius-spam-protection-plugin
   ```

2. If you do not use Symfony Flex, enable the bundle and the plugin:

   ```php
   // config/bundles.php
   return [
       // ...
       FlorentinGarnier\SpamProtectionBundle\FlorentinGarnierSpamProtectionBundle::class => ['all' => true],
       Atriametal\SyliusSpamProtectionPlugin\AtriametalSyliusSpamProtectionPlugin::class => ['all' => true],
   ];
   ```

3. Load the JavaScript solver in your shop theme, as explained in the
   [bundle documentation](https://github.com/FlorentinGarnier/spam-protection-bundle#3-load-the-javascript-solver).
   Without it, the protected forms cannot be submitted.

4. Download the IP reputation lists, then schedule the command daily:

   ```bash
   bin/console spam-protection:refresh-ip-lists
   ```

Configuration is optional: see the [bundle documentation](https://github.com/FlorentinGarnier/spam-protection-bundle#configuration).

## How the fields are rendered

The Sylius shop templates end their forms with `render_rest: false`, so a field added to a form is not rendered
automatically. The plugin renders the protection with a priority of -100 in the forms' template events on
Sylius 1:

- `sylius.shop.contact.request.form`
- `sylius.shop.register.form`
- `sylius.shop.request_password_reset_token.form`

and in the forms' Twig hooks on Sylius 2:

- `sylius_shop.contact.contact_request.content.form`
- `sylius_shop.account.register.content.form`
- `sylius_shop.account.forgotten_password.content.form_container.form`

The Sylius shop does not render the errors of these forms, so the plugin also renders its rejection message at the
top of each form, with a priority of 1000 (block `atriametal_spam_protection_error`). If your theme renders
the errors of a form itself (`{{ form_errors(form) }}`), remove this block from its event or hook, as shown below,
so that the message appears once.

If your theme overrides these templates:

- **It still calls the template events or hooks:** nothing to do.
- **It renders `form.spam_protection` itself:** nothing to do either. The plugin skips a field that is already
  rendered.
- **It calls neither:** render the field in the form with `{{ form_row(form.spam_protection) }}`.

To remove a block from an event or a hook, for example when your theme renders the field elsewhere
(`atriametal_spam_protection`) or the errors of the form (`atriametal_spam_protection_error`):

```yaml
# Sylius 1: config/packages/sylius_ui.yaml
sylius_ui:
    events:
        sylius.shop.contact.request.form:
            blocks:
                atriametal_spam_protection: false
```

```yaml
# Sylius 2: config/packages/sylius_twig_hooks.yaml
sylius_twig_hooks:
    hooks:
        sylius_shop.contact.contact_request.content.form:
            atriametal_spam_protection:
                enabled: false
```

## Suspicious contact requests

Spam sent by a real browser and worded like a customer's message passes every check. What gives it away is
where it comes from, a datacenter, a VPN or a Tor exit node, although some customers browse through a VPN too.

- **With a link to another site**, the contact request is rejected. Links to the host of the request are always
  accepted, and so are those to the `own_hosts` of the
  [bundle configuration](https://github.com/FlorentinGarnier/spam-protection-bundle#configuration): declare there
  the other domains of a multi-domain shop.
- **Without such a link**, it is sent, and flagged for its recipient: `[Spam?] ` is prepended to the subject of
  the contact e-mail, and the header `X-Spam-Protection-Ip-Risk` gives the risk level (`hosting` or `tor`), for
  your mailbox rules.

Contact requests sent from any other address are never flagged, whatever they link to.

To change the prefix, or to flag the e-mails that your own protected forms send to the shop:

```yaml
# config/services.yaml
parameters:
    atriametal_sylius_spam_protection.suspicious_subject_prefix: '[Suspect] '
    atriametal_sylius_spam_protection.suspicious_email_codes: ['contact_request', 'quotation_request']
```

The codes are those of the e-mails declared in `sylius_mailer.emails`.
List only e-mails sent to the shop: a customer must not read that you suspect them. An e-mail is flagged when it
is sent during the request of the visitor, not from a queue consumed later.

## Protecting other forms

Any Symfony form can be protected with the bundle's form type, including your own shop forms:

```php
use FlorentinGarnier\SpamProtectionBundle\Form\SpamProtectionType;

$builder->add('spam_protection', SpamProtectionType::class, [
    'protection_scope' => 'quotation',
    'content_fields' => ['message'],
]);
```

## Testing

The integration tests render the shop forms with the
[Sylius Test Application](https://github.com/Sylius/TestApplication). They need no database.

```bash
composer install
vendor/bin/phpunit                  # unit and integration tests
vendor/bin/ecs check                # Sylius coding standard
vendor/bin/phpstan analyse          # static analysis
```

Without restriction, Composer may mix Symfony versions (Behat does not support Symfony 8 yet, for instance). Choose
the Sylius and Symfony versions to test, among the combinations listed in the [requirements](#requirements);
Symfony Flex applies `SYMFONY_REQUIRE` to every Symfony package:

```bash
SYMFONY_REQUIRE="~6.4.0" composer update --with "sylius/sylius:~1.14.0" -W
rm -rf var/cache
```

Behat supports neither Symfony 5.4 nor Symfony 8: to test them, remove it first, as the CI does
(see [ci.yml](.github/workflows/ci.yml)).

### Behat

The Behat scenarios submit the shop forms like a visitor: from Chrome, which solves the proof of work, and without
JavaScript, like a bot. They run on Sylius 2 and need MySQL, Chrome and the
[Symfony CLI](https://symfony.com/download).

```bash
docker compose up -d                # MySQL on port 3306 (MYSQL_PORT=3307 docker compose up -d to change it)
(cd vendor/sylius/test-application && yarn install && yarn build)
APP_ENV=test vendor/bin/console doctrine:database:create
APP_ENV=test vendor/bin/console doctrine:migrations:migrate -n

APP_ENV=test symfony server:start --port=8080 --daemon --no-tls
google-chrome --headless=new --remote-debugging-port=9222 &

vendor/bin/behat
```

With another database URL, set `DATABASE_URL` in `tests/TestApplication/.env.test.local`. Failed steps leave a
screenshot and the page in `etc/build/`, and the verdicts of the protection are logged to
`var/log/spam_protection.log`.

## Contributing

Contributions are welcome. Please read [CONTRIBUTING.md](CONTRIBUTING.md) and the
[Code of Conduct](CODE_OF_CONDUCT.md). Report security issues privately, as
described in [SECURITY.md](SECURITY.md).

## License

Released under the [MIT License](LICENSE).
