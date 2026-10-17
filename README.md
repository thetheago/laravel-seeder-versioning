# Laravel Seeder Versioning

A Laravel package that provides version control for database seeders, similar to Laravel's migration system. This package tracks which seeders have been run and only executes seeders that have been modified by monitoring class content changes.

## Features

- Tracks seeder execution history
- Only runs modified seeders
- Validates seeder content changes using hash verification
- Simple integration with Laravel's existing seeder system
- Option to generate hash versioning without running seeders

## Installation / Configuration

You can install the package via composer:

```bash
composer require thetheago/laravel-seeder-versioning
```

Publish the package configuration and migrations:

```bash
php artisan vendor:publish --tag=seeder-versioning
```

Run the migrations to create the seeder versioning table:

```bash
php artisan migrate
```

## Usage

### Running Seeders

To run your seeders with version control:

```bash
php artisan seed:migrate
```

This command will:
1. Check all your seeders
2. Compare their content against previously run versions
3. Only execute seeders that have been modified or haven't been run before

In production (`APP_ENV=production`) the command asks for confirmation before running, like `db:seed`. Use `--force` to skip the question (deploys, CI):

```bash
php artisan seed:migrate --force
```

### Hash Generation Only

If you want to generate hash versions for your seeders without executing them:

```bash
php artisan seed:migrate --hash-only
```

This is useful for:
- Initial setup in existing projects
- Verifying which seeders would be executed
- Updating the version control table without running seeders

### Checking the status

To see what `seed:migrate` would run, without running anything:

```bash
php artisan seed:status
```

```text
Last seeder run: UserSeeder (c9fd9cfb3e17) at 2026-10-17 17:29:36

+---------------+----------+--------------+
| Seeder        | Status   | Hash         |
+---------------+----------+--------------+
| ProductSeeder | new      | cd59479316bd |
| UserSeeder    | modified | dfd2ab78ee0a |
+---------------+----------+--------------+
2 seeders pending. Run php artisan seed:migrate to execute them.
```

- `new`: never run; `modified`: changed since its last run (the hash is the file's current one).
- Use `--all` to also list the seeders that are `up to date`.

## How It Works

The package creates a `seeder_versioning` table that stores:
- Seeder class names
- Content hashes
- Execution timestamps

Every time a seeder is registered, a row is also added to the `seeder_version_history` table
(configurable with `history_table`) with the seeder name, the new hash, the previous hash
(`null` the first time), whether it was a `--hash-only` run, and when it happened. This lets
you see how many times a seeder ran and when its content changed.

If you are upgrading and already have the versions table, the history table is created
automatically on the next `seed:migrate`. If you manage the package migrations yourself,
publish them again with `php artisan vendor:publish --tag=seeder-versioning`.

When you run `seed:migrate`, the package:
1. Scans your seeder directory
2. Generates a hash for each seeder's content
3. Compares against stored hashes
4. Executes only modified or new seeders

### Excluding seeders

`DatabaseSeeder` is skipped by default: it calls the other seeders, so running it would run them twice. To skip other seeders, add them to `except` in `config/seeder-versioning.php`, with or without namespace:

```php
'except' => [
    'DatabaseSeeder',
    \Database\Seeders\LocalDevSeeder::class,
],
```

Excluded seeders are never run or registered in the versioning table.

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the MIT license.
