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

### Hash Generation Only

If you want to generate hash versions for your seeders without executing them:

```bash
php artisan seed:migrate --hash-only
```

This is useful for:
- Initial setup in existing projects
- Verifying which seeders would be executed
- Updating the version control table without running seeders

## How It Works

The package creates a `seeder_versioning` table that stores:
- Seeder class names
- Content hashes
- Execution timestamps

When you run `seed:migrate`, the package:
1. Scans your seeder directory
2. Generates a hash for each seeder's content
3. Compares against stored hashes
4. Executes only modified or new seeders

## Contributing

Contributions are welcome! Please feel free to submit a Pull Request.

## License

This package is open-sourced software licensed under the MIT license.
