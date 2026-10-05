# contenir-db-model-tools

Command-line tools for [contenir-db-model](https://github.com/contenir/contenir-db-model) 2.x:

- **`entity:generate`** writes typed, attribute-mapped entity classes from
  live database tables.
- **`entity:upgrade`** converts contenir-db-model 1.x entities
  (`AbstractEntity` with `$columns` arrays) to 2.x attributes in place.
- **`mapping:validate`** checks entity mappings against the live schema.
  It catches missing columns, nullability and type drift, and key
  mismatches, so you can use it in CI.

They work with MySQL, PostgreSQL and SQLite through
[php-db/phpdb](https://github.com/php-db/phpdb).

## Requirements

- PHP 8.3, 8.4 or 8.5
- `contenir/contenir-db-model` 2.x
- The phpdb platform package for your database: `php-db/phpdb-mysql`,
  `php-db/phpdb-pgsql` or `php-db/phpdb-sqlite`

## Installation

```bash
composer require --dev contenir/contenir-db-model-tools:^1.0@RC php-db/phpdb-mysql
```

Until `php-db/phpdb` 0.6.0 and contenir-db-model 2.0.0 are tagged, the
project needs `"minimum-stability": "dev"` and `"prefer-stable": true`.

## Usage

Standalone, with a DSN:

```bash
vendor/bin/db-model entity:generate --all --dsn "mysql://app:secret@127.0.0.1/app"
vendor/bin/db-model entity:upgrade src/Entity --dsn "$DATABASE_URL"
vendor/bin/db-model mapping:validate --path src/Entity --dsn "$DATABASE_URL"
```

Through laminas-cli, the commands are `db-model:entity:generate`,
`db-model:entity:upgrade` and `db-model:mapping:validate`. They use your
application's `contenir_db_model.adapter`, so `--dsn` is optional:

```bash
vendor/bin/laminas db-model:mapping:validate --path src/Entity
```

## Documentation

- [Setup and connections](docs/setup.md): DSNs, laminas-cli and exit codes
- [Generating entities](docs/generate.md): `entity:generate`, the type map
  and relation inference
- [Upgrading 1.x entities](docs/upgrade.md): `entity:upgrade`, what changes
  and what to check afterwards
- [Validating mappings](docs/validate.md): `mapping:validate` and every
  check it runs
- [`llms.txt`](llms.txt): a condensed reference for LLMs

## Development

```bash
composer install
composer check               # Mago lint and analysis, then the unit and integration suites (SQLite)
composer test-integration    # integration suite only, SQLite by default
```

The integration suite also runs against MySQL and PostgreSQL. Start them
with `docker compose up -d`, then run:

```bash
DB_PLATFORM=mysql DB_PORT=33306 DB_PASSWORD=secret composer test-integration
DB_PLATFORM=pgsql DB_PORT=55432 DB_USER=postgres DB_PASSWORD=secret composer test-integration
```

## License

BSD-3-Clause. See [LICENSE.md](LICENSE.md).
