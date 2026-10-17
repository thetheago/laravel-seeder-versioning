# Contributing

Thanks for helping! Bugs and ideas live in the [issues](https://github.com/thetheago/laravel-seeder-versioning/issues) — look for `good first issue` and `hacktoberfest`.

## Setup

Requires PHP 8.3+ and Composer.

```bash
git clone git@github.com:<your-user>/laravel-seeder-versioning.git
cd laravel-seeder-versioning
composer install
composer test
```

## Workflow

1. Comment on the issue you want to take, so nobody duplicates work.
2. Create a branch from `main`.
3. Add or update tests for your change; `composer test` must pass.
4. Follow [PSR-12](https://www.php-fig.org/psr/psr-12/).
5. Use [Conventional Commits](https://www.conventionalcommits.org/) (`fix:`, `feat:`, `docs:`, `chore:`...).
6. Open a PR referencing the issue (`Closes #123`) and add a line to `CHANGELOG.md` under `Unreleased`.

## No runtime dependency on AI

You may use AI tools to write your contribution, but the package itself must never need one to work. No command, feature or test may call an LLM or any external AI service. Everything runs in plain PHP and is deterministic: the same input always gives the same output.

By contributing you agree to follow the [Code of Conduct](CODE_OF_CONDUCT.md).
