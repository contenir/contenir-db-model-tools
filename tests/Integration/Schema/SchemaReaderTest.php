<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Integration\Schema;

use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Generator\TypeMapper;
use Contenir\Db\Model\Tools\Schema\ColumnExtras;
use Contenir\Db\Model\Tools\Schema\ColumnSchema;
use Contenir\Db\Model\Tools\Schema\Constraints;
use Contenir\Db\Model\Tools\Schema\ForeignKeySchema;
use Contenir\Db\Model\Tools\Schema\Platform;
use Contenir\Db\Model\Tools\Schema\SchemaReader;
use Contenir\Db\Model\Tools\Schema\TableSchema;
use ContenirTest\Db\Model\Tools\Trait\TestDatabaseTrait;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function sort;

/**
 * Runs on the platform named by DB_PLATFORM, so assertions are made on what
 * the generator derives from each column rather than on dialect type names.
 */
#[CoversClass(SchemaReader::class)]
#[CoversClass(ColumnExtras::class)]
#[CoversClass(Constraints::class)]
#[CoversClass(Platform::class)]
#[CoversClass(TableSchema::class)]
#[CoversClass(ColumnSchema::class)]
#[CoversClass(ForeignKeySchema::class)]
#[CoversClass(ToolException::class)]
#[Group('integration')]
final class SchemaReaderTest extends TestCase
{
    use TestDatabaseTrait;

    /**
     * @return list<array{string, string, string|null, bool, bool, int|float|bool|null}>
     */
    private static function describe(TableSchema $table): array
    {
        return array_map(static function (ColumnSchema $column): array {
            $type = TypeMapper::map($column);

            return [
                $column->name,
                $type->phpType,
                $type->converter,
                $column->nullable,
                $column->generated,
                TypeMapper::defaultValue($column, $type),
            ];
        }, $table->columns);
    }

    #[Test]
    public function compositeKeyColumnsAreNotGenerated(): void
    {
        $table = (new SchemaReader($this->adapter))->read('user_tag');

        static::assertSame([['user_id', 'tag_id'], false, false], [
            $table->primaryKey,
            $table->columns[0]->generated,
            $table->columns[1]->generated,
        ]);
    }

    #[Test]
    public function listsTables(): void
    {
        $names = (new SchemaReader($this->adapter))->tableNames();
        sort($names);

        static::assertSame(['audit_log', 'order_items', 'user_tag', 'users'], $names);
    }

    #[Test]
    public function readsColumnTypesNullabilityDefaultsAndGeneratedKeys(): void
    {
        static::assertSame(
            [
                ['id',         'int',                    null,   false, true,  null],
                ['email',      'string',                 null,   false, false, null],
                ['active',     'bool',                   null,   false, false, true],
                ['created_at', DateTimeImmutable::class, null,   false, false, null],
                ['birthday',   DateTimeImmutable::class, 'date', true,  false, null],
                ['meta',       'array',                  'json', true,  false, null],
                ['price',      'string',                 null,   true,  false, null],
                ['score',      'float',                  null,   false, false, 1.5],
                ['version',    'int',                    null,   false, false, 1],
            ],
            self::describe((new SchemaReader($this->adapter))->read('users')),
        );
    }

    #[Test]
    public function readsKeysAndForeignKeys(): void
    {
        $table = (new SchemaReader($this->adapter))->read('order_items');

        static::assertEquals(
            [['id'], [new ForeignKeySchema(['user_id'], 'users', ['id'])], true, false, 'quantity', null, false],
            [
                $table->primaryKey,
                $table->foreignKeys,
                $table->isPrimaryKey('id'),
                $table->isPrimaryKey('user_id'),
                $table->column('quantity')?->name,
                $table->column('missing'),
                $table->columns[0]->generated,
            ],
        );
    }

    #[Test]
    public function tableWithoutPrimaryKeyHasNoKey(): void
    {
        static::assertSame([], (new SchemaReader($this->adapter))->read('audit_log')->primaryKey);
    }

    #[Test]
    public function unknownTableFails(): void
    {
        $this->expectExceptionObject(ToolException::tableNotFound('nope'));

        (new SchemaReader($this->adapter))->read('nope');
    }

    protected function setUp(): void
    {
        $this->setUpTestDatabase();
    }
}
