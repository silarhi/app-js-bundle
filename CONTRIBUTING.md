# Contributing

Bug reports and feature requests go to the [issue tracker](https://github.com/silarhi/app-js-bundle/issues), changes
to a pull request against `main`.

## Setup

```bash
composer install
pnpm install # Git hooks: lint-staged formats the staged files on commit
```

## Quality checks

The CI runs PHPUnit on every supported PHP version, with the lowest and the latest dependencies. PHPStan runs once on
the latest PHP and dependencies; PHP CS Fixer and the `composer.json` checks run once too. It does not run Rector: run
it locally before opening a pull request.

```bash
vendor/bin/phpunit
vendor/bin/phpstan analyse
vendor/bin/php-cs-fixer fix --dry-run --diff
vendor/bin/rector process --dry-run
composer validate --strict
composer normalize --dry-run
```
