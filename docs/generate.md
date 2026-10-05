# Generating entities: `entity:generate`

`entity:generate` reads live tables and writes a contenir-db-model 2.x
entity class for each one.

```bash
vendor/bin/db-model entity:generate users orders --dsn "$DATABASE_URL"
vendor/bin/db-model entity:generate --all --namespace 'App\Entity' --output src/Entity --version-column version
```

## Options

| Option | Default | Meaning |
| --- | --- | --- |
| `tables...` | | Tables to generate |
| `--all` | | Every table in the schema instead |
| `--namespace` | `App\Entity` | Namespace of the classes |
| `--output` | `src/Entity` | Directory to write `<Class>.php` files to. Created if missing |
| `--class` | | Class name, when generating a single table |
| `--version-column` | | Column to map as `#[Version]`, in every table that has it |
| `--no-relations` | | Don't turn foreign keys into relations |
| `--schema` | connection default | Schema to read |
| `--force` | | Overwrite existing files. Without it, an existing file is an error |
| `--dry-run` | | Print the classes instead of writing them |
| `--dsn` | | Connection; see [setup](setup.md) |

## What is generated

Given:

```sql
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL REFERENCES users (id),
    quantity INT NOT NULL DEFAULT 1,
    note TEXT NULL,
    shipped_at DATETIME NULL,
    options JSON NULL
);
```

`entity:generate order_items` writes `src/Entity/OrderItem.php`:

```php
<?php

declare(strict_types=1);

namespace App\Entity;

use Contenir\Db\Model\Mapping\BelongsTo;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Relation\LazyRelationsTrait;
use DateTimeImmutable;

#[Table('order_items')]
final class OrderItem
{
    use LazyRelationsTrait;

    #[Id(generated: true)]
    public ?int $id = null;

    #[Column('user_id')]
    public int $userId;

    #[Column]
    public int $quantity = 1;

    #[Column]
    public ?string $note = null;

    #[Column('shipped_at')]
    public ?DateTimeImmutable $shippedAt = null;

    #[Column(type: 'json')]
    public ?array $options = null;

    #[BelongsTo(User::class, foreignKey: 'user_id', ownerKey: 'id')]
    public User $user;
}
```

### Names

- **Classes** are the singular, PascalCase table name: `order_items` →
  `OrderItem`, `categories` → `Category`. Use `--class` to choose one.
- **Properties** are camelCase column names. A column whose name differs
  from its property gets `#[Column('column_name')]`.

### Types

| Column type | Property type |
| --- | --- |
| Integer types, `serial` | `int` |
| `float`, `double`, `real` | `float` |
| `boolean`, `bit`, MySQL `tinyint(1)` | `bool` |
| `datetime`, `timestamp` (with or without time zone) | `DateTimeImmutable` |
| `date` | `DateTimeImmutable` with `#[Column(type: 'date')]` |
| `json`, `jsonb` | `array` with `#[Column(type: 'json')]` |
| `decimal`, `numeric`, text types, anything else | `string` |

`decimal` maps to `string` to keep exact values. Change it to `float`
yourself if rounding is acceptable.

### Nullability and defaults

- Nullable columns get a nullable type and `= null`.
- Integer, float and boolean defaults become property defaults
  (`DEFAULT 1` → `= 1`). Other defaults, such as strings or expressions,
  are left to the database.
- Required columns without a default have no property default, so they
  must be set before saving.

### Keys

- Primary-key columns get `#[Id]`, one per column for composite keys.
- Database-generated keys get `#[Id(generated: true)]` and a nullable
  type. That means MySQL `AUTO_INCREMENT`, PostgreSQL `serial`/identity
  columns, and SQLite's `INTEGER PRIMARY KEY`.
- `--version-column` maps that column as `#[Version]` in every table that
  has it.

### Relations

Each foreign key becomes a lazy `#[BelongsTo]` relation, and the class
uses `LazyRelationsTrait`:

- **Name:** the foreign-key column without `_id` (`author_id` →
  `$author`). Otherwise it is the singular referenced table. If that name
  is already taken by a column property, `Entity` is appended.
- **Type:** the referenced table's class. It is nullable when the
  foreign-key column is.
- **Target class:** it must exist in the same namespace. Generate the
  referenced tables too, or edit the target.

The inverse sides (`#[HasMany]`, `#[HasOne]`, `#[ManyToMany]`) can't be
inferred reliably from a schema. Add them by hand where you need them.
See contenir-db-model's relations documentation.

## After generating

Generated classes are a starting point. Review them:

- **Enums and secrets.** Swap `string` for a backed enum, or for
  `SensitiveString` on secrets such as password hashes.
- **Required columns.** Consider making required columns constructor
  arguments.
- **Inverse relations.** Add the relations described above.
- **Validation.** Run [`mapping:validate`](validate.md) after editing.
