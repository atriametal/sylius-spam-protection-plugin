# Contributing

Thank you for considering a contribution! By participating in this project, you agree to abide by its
[Code of Conduct](CODE_OF_CONDUCT.md).

## Reporting a bug or suggesting a feature

Open an issue with the [bug report or feature request form](https://github.com/atriametal/sylius-spam-protection-plugin/issues/new/choose). For security issues, do **not** open an issue: follow
[SECURITY.md](SECURITY.md).

## Submitting a pull request

1. Fork the repository and create a branch from `main`.
2. Install the dependencies: `composer install`.
3. Write a test that fails without your change, then make it pass: `vendor/bin/phpunit`.
4. Check the code style and the types: `vendor/bin/ecs check` (Sylius coding standard) and
   `vendor/bin/phpstan analyse`. Keep `declare(strict_types=1);` and the license header in every PHP file.
   The plugin supports Sylius 1.14 and 2.x: see the README to run the tests against another version.
5. Describe the change in the `Unreleased` section of [CHANGELOG.md](CHANGELOG.md).
6. Open the pull request, explaining the problem it solves.

Keep pull requests focused: one change per pull request is easier to review. Changes to the rejection reasons or
to the token format are breaking changes, because applications rely on them in their logs and dashboards.
