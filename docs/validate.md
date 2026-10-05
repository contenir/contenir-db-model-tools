# Validating mappings: `mapping:validate`

`mapping:validate` compares entity mappings with the live schema. It
catches drift after migrations, before it surfaces as a runtime
`TypeConversionException` or a failed insert.

```bash
vendor/bin/db-model mapping:validate --path src/Entity --dsn "$DATABASE_URL"
vendor/bin/db-model mapping:validate 'App\Entity\User' 'App\Entity\Order'
```

| Option | Meaning |
| --- | --- |
| `classes...` | Entity classes to check |
| `--path` | Directory to scan for `#[Table]` classes. Repeatable |
| `--strict` | Fail on warnings as well as errors |
| `--dsn` | Connection; see [setup](setup.md) |

**Finding classes.** `--path` parses source files for classes carrying
`#[Table]`, without loading files that aren't entities. Classes must be
autoloadable by your project's autoloader.

## Output

```text
OK   App\Entity\User
WARN App\Entity\AuditLog
       warning Table "audit_log" has no primary key; #[Id] column(s) message must still be unique
FAIL App\Entity\Order
       error Column "shipped_at" allows NULL but property $shippedAt is not nullable
       warning Property $total is int but column "total" is decimal (string)
3 entities checked: 1 errors, 2 warnings
```

The exit code is `1` when there are errors, or warnings with `--strict`.
Otherwise it is `0`, so the command can gate CI.

## Checks

**Errors** break loading or saving:

| Check | Example |
| --- | --- |
| Invalid mapping | Anything contenir-db-model's `MappingException` reports |
| Class cannot be autoloaded | |
| Table does not exist | |
| Mapped column does not exist | `Column "nickname" (property $nickname) does not exist` |
| Nullable column, non-nullable property | Loading NULL would throw |
| `#[Id]` columns differ from the primary key | |
| `#[Id(generated: true)]` on a column the database doesn't generate | |
| `#[Version]` on a non-integer column | |
| Join table or join column of a `ManyToMany` missing | |

**Warnings** are likely mistakes that may still work:

| Check | Why |
| --- | --- |
| Property type doesn't match the column type | e.g. an `int` property on a `varchar` column |
| Nullable property, `NOT NULL` column without a default | Saving null fails |
| Generated column mapped as plain `#[Id]` | Map it `#[Id(generated: true)]` so the key is written back |
| Unmapped `NOT NULL` column without a default | Inserts fail, unless a trigger fills it |
| Table without a primary key | Identity relies on the mapped `#[Id]` being unique |

**Type matching** is deliberately lenient:

- `string` properties match any column.
- Backed enums match their backing type.
- `SensitiveString` matches text.
- `int` and `bool` are interchangeable.
- `float` accepts `decimal`; `array` accepts text columns holding JSON.
- Classes with custom converters are not checked.

Relation key columns are checked as ordinary mapped columns of each
entity.
