# Setup and connections

## Installation

Install as a development dependency, with the phpdb platform package for
your database:

```bash
composer require --dev contenir/contenir-db-model-tools:^1.0@RC php-db/phpdb-mysql
```

| Database | Platform package |
| --- | --- |
| MySQL / MariaDB | `php-db/phpdb-mysql` |
| PostgreSQL | `php-db/phpdb-pgsql` |
| SQLite | `php-db/phpdb-sqlite` |

Until `php-db/phpdb` 0.6.0 and contenir-db-model 2.0.0 are tagged, the
project needs `"minimum-stability": "dev"` and `"prefer-stable": true`.

## Running the commands

There are two ways to run the commands. They behave the same; only the
command names and the source of the connection differ.

### Standalone: `vendor/bin/db-model`

Pass the database as a DSN URL:

```bash
vendor/bin/db-model entity:generate users --dsn "mysql://app:secret@127.0.0.1:3306/app"
```

| Database | DSN |
| --- | --- |
| MySQL | `mysql://user:password@host:3306/database` |
| PostgreSQL | `pgsql://user:password@host:5432/database` (`postgres://` also works) |
| SQLite file | `sqlite:///absolute/path/app.db` or `sqlite:relative/app.db` |
| SQLite in memory | `sqlite::memory:` |

- URL-encode special characters in the user name or password.
- The password is never shown: connection errors print the DSN with it
  replaced by `***`.
- Keep real credentials out of shell history, for example
  `--dsn "$DATABASE_URL"`.

`vendor/bin/db-model` uses your project's Composer autoloader, so
`mapping:validate` can load your entity classes.

### Through laminas-cli

With `laminas/laminas-cli` and `laminas/laminas-component-installer`, the
package's `ConfigProvider` (Mezzio) or `Module` (laminas-mvc) is registered
automatically. The commands are then available as:

| laminas-cli | Standalone |
| --- | --- |
| `vendor/bin/laminas db-model:entity:generate` | `vendor/bin/db-model entity:generate` |
| `vendor/bin/laminas db-model:entity:upgrade` | `vendor/bin/db-model entity:upgrade` |
| `vendor/bin/laminas db-model:mapping:validate` | `vendor/bin/db-model mapping:validate` |

The commands use your application's configured connection, so `--dsn` is
optional:

- **The adapter** is the service named by `contenir_db_model.adapter`, the
  same setting contenir-db-model uses (default
  `PhpDb\Adapter\AdapterInterface`).
- **`mapping:validate`** also uses the application's
  `MetadataFactoryInterface` service when there is one. Note that a
  `CachedMetadataFactory` returns cached metadata, so clear the cache
  before validating changed entities.

`--dsn` still overrides the configured adapter.

## Schemas

`--schema` reads tables from a named schema: a PostgreSQL schema, or a
MySQL database other than the connection's. Without it the connection's
default is used (`public` on PostgreSQL).

## Errors

Commands exit with:

| Code | Meaning |
| --- | --- |
| `0` | Success |
| `1` | Failure: a connection, schema, file or mapping error, reported on screen |
| `2` | Invalid arguments, such as no tables named |
