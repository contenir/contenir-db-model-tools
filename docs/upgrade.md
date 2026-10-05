# Upgrading 1.x entities: `entity:upgrade`

`entity:upgrade` converts contenir-db-model 1.x entities (classes
extending `AbstractEntity` with `$columns`, `$primaryKeys`,
`$versionColumn` and `$relations` arrays) to 2.x attributes and typed
properties. It rewrites each file **in place** and leaves everything else
in it untouched.

```bash
git commit -am "Before entity upgrade"     # the upgrade rewrites files in place
vendor/bin/db-model entity:upgrade src/Entity --dsn "$DATABASE_URL"
vendor/bin/db-model mapping:validate --path src/Entity
```

| Option | Meaning |
| --- | --- |
| `paths...` | Entity files, or directories to scan. Files without a 1.x entity are skipped |
| `--table` | The table, when upgrading a single file whose 1.x repository can't be found |
| `--camel-case` | Rename properties to camelCase (`created_at` → `$createdAt`) |
| `--map` | `Repository=Entity` class pair for relation targets. Repeatable |
| `--dry-run` | Print the upgraded files instead of writing them |
| `--output` | Write to this directory instead of in place |
| `--force` | Overwrite existing files in `--output` |
| `--schema`, `--dsn` | See [setup](setup.md) |

## What changes

Before:

```php
namespace App\Entity;

use App\Repository\OrderRepository;
use Contenir\Db\Model\Entity\AbstractEntity;

class UserEntity extends AbstractEntity
{
    public const ROLE_ADMIN = 'admin';

    protected array $primaryKeys = ['id'];
    protected array $columns = ['id', 'email', 'created_at', 'version'];
    protected ?string $versionColumn = 'version';
    protected array $relations = [
        'orders' => [
            'type'   => self::RELATION_MANY,
            'column' => 'id',
            'table'  => ['class' => OrderRepository::class, 'column' => 'user_id'],
            'order'  => ['created_at DESC'],
        ],
    ];

    public function isAdmin(): bool
    {
        return str_ends_with($this->email, '@example.com');
    }
}
```

After:

```php
namespace App\Entity;

use Contenir\Db\Model\Collection;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\HasMany;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Mapping\Version;
use DateTimeImmutable;

#[Table('users')]
class UserEntity
{
    public const ROLE_ADMIN = 'admin';

    #[Id(generated: true)]
    public ?int $id = null;

    #[Column]
    public string $email;

    #[Column]
    public DateTimeImmutable $created_at;

    #[Version]
    public int $version = 1;

    /** @var Collection<OrderEntity> */
    #[HasMany(OrderEntity::class, foreignKey: 'user_id', orderBy: ['created_at' => 'DESC'])]
    public Collection $orders;

    public function isAdmin(): bool
    {
        return str_ends_with($this->email, '@example.com');
    }
}
```

**Removed:**

- `extends AbstractEntity` / `BaseEntity`, and `implements EntityInterface`;
- the four mapping properties;
- the imports of 1.x entity classes, and of the repositories the relations
  named.

**Added:**

- `#[Table]`;
- a typed, attributed property for each column and relation;
- `LazyRelationsTrait` when there are single relations;
- the imports for all of these.

**Kept:** the class name, namespace, constants, methods, other properties,
interfaces, docblocks and formatting. Mapped properties go after the
class's constants and trait uses.

### Property names

Properties keep their **column names** by default (`public
DateTimeImmutable $created_at`). 1.x code read columns as
`$entity->created_at`, so that code keeps working. Column names that
aren't valid PHP property names are camel-cased.

Pass `--camel-case` for `$createdAt` with `#[Column('created_at')]`. Your
code that reads the old names must then be updated.

### Columns and types

- **Columns.** The columns listed in `$columns` are mapped, in that order,
  or every column of the table if the list is empty.
- **Types, nullability and defaults** come from the live table, exactly as
  in [`entity:generate`](generate.md#types).
- **Keys.** `$primaryKeys` becomes `#[Id]`, or the table's primary key
  when it's empty. Generated keys are detected from the database.

### The table

1.x entities didn't name their table; their repository did. The table is
read from the `$table` of the paired repository, following 1.x's naming
convention:

```text
src/Entity/UserEntity.php      → src/Repository/UserRepository.php   ($table = 'users')
src/Entity/Admin/RoleEntity.php → src/Repository/Admin/RoleRepository.php
```

- `$table` may be a string, or an alias array such as `['u' => 'users']`.
- Otherwise, upgrade the file on its own with `--table`.

### Relations

| 1.x | 2.x |
| --- | --- |
| `via` set | `#[ManyToMany(T::class, via: new Via(…))]` |
| `single`, `column` is this entity's primary key | `#[HasOne(T::class, foreignKey: table.column)]` |
| `single`, any other `column` | `#[BelongsTo(T::class, foreignKey: column, ownerKey: table.column)]` |
| `many` (the default) | `#[HasMany(T::class, foreignKey: table.column)]` |

Details:

- **Keys.** `column` that isn't the primary key becomes `localKey`.
  Target-side keys (`ownerKey`, `targetKey`) are always written out,
  because the target's primary key isn't known while upgrading this
  entity.
- **`via` keys.** `via.column` becomes `foreignKey` and `via.join` becomes
  `relatedKey`, with the same defaults as 1.x.
- **`order`.** `['created_at DESC']` or `['created_at' => 'DESC']` becomes
  `orderBy`.
- **`where`.** Only `column => value` equality is kept, as `where`.
- **Property types.** To-many relations are typed `Collection` with a
  `@var` docblock. Single relations are typed `?Target` (1.x returned
  `false` for a missing row; 2.x returns `null`).
- **Targets.** `table.class` named a **repository**. The target entity
  follows 1.x's convention (`App\Repository\OrderRepository` →
  `App\Entity\OrderEntity`), the same one 1.x's `RepositoryFactory` used.
- **Your own map.** If you configured `model.map` instead, pass the same
  pairs: `--map 'App\Repository\Orders=App\Model\Order'`.

## What it reports

Anything that can't be converted automatically is listed under the file
as `check` lines:

```text
Upgraded App\Entity\UserEntity → src/Entity/UserEntity.php
  check relation $orders: where condition "quantity > 0" was dropped; 2.x supports column => value equality only
  check method toArray() uses the 1.x entity API; rewrite it for typed properties
```

- **Dropped `where` and `order` settings.** Conditions that aren't
  equality, and `where`/`order` on a `BelongsTo`, which takes neither.
- **Methods using the 1.x API.** Methods using `$this->data`, `parent::`
  calls (including constructors that called `parent::__construct($data)`)
  or `populate()`, `getArrayCopy()`, `exchangeArray()`, `nextVersion()`
  and similar.
- **Another parent class.** A class extending something other than
  `AbstractEntity`/`BaseEntity`, such as your own base entity. Upgrade
  that class too.

A file that can't be upgraded is reported as an error and left alone; the
others are still upgraded. Causes include a missing table, a listed column
the table lacks, a relation without `column` or `table.class`, and mapping
that isn't a constant expression.

## After upgrading

1. Run your code formatter. New members are printed in a standard style.
2. Work through the `check` lines.
3. Run `mapping:validate` on the upgraded entities.
4. Follow contenir-db-model's `UPGRADE-2.0.md` for repositories, saving
   and configuration, which this command doesn't touch.
