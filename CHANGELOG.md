# Changelog

All notable changes to this project are documented here. The format follows
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project
adheres to [Semantic Versioning](https://semver.org/).

## [1.0.0] - Unreleased

First release, for contenir-db-model 2.x.

### Added

- **`entity:generate`** writes entity classes from live tables:
  - typed properties, with converters for date and JSON columns;
  - generated keys and version columns;
  - literal defaults;
  - foreign keys as lazy `#[BelongsTo]` relations.
- **`entity:upgrade`** converts 1.x `AbstractEntity` classes to 2.x
  attributes in place:
  - it keeps the class name, namespace and every other member;
  - it types columns from the live table and converts relation arrays to
    relation attributes;
  - it reports what needs review by hand.
- **`mapping:validate`** checks entity mappings against the live schema:
  - missing tables and columns, key and generated-key mismatches;
  - nullability and type drift, version-column types;
  - unmapped required columns and missing join tables.
- **Usage.** A standalone `db-model` binary taking a `--dsn` URL, and a
  laminas-cli `ConfigProvider` / laminas-mvc `Module` that use the
  application's `contenir_db_model.adapter`.
- **Platforms.** MySQL, PostgreSQL and SQLite, through phpdb's platform
  packages.
