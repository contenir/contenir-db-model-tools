<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Generator;

use Contenir\Db\Model\Tools\Generator\ColumnRenderer;
use Contenir\Db\Model\Tools\Generator\EntityGenerator;
use Contenir\Db\Model\Tools\Generator\EntityOptions;
use Contenir\Db\Model\Tools\Generator\GeneratedClass;
use Contenir\Db\Model\Tools\Generator\RelationRenderer;
use Contenir\Db\Model\Tools\Schema\ColumnSchema;
use Contenir\Db\Model\Tools\Schema\ForeignKeySchema;
use Contenir\Db\Model\Tools\Schema\TableSchema;
use ContenirTest\Db\Model\Tools\TestAsset\GeneratedClassLoader;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function str_contains;

#[CoversClass(EntityGenerator::class)]
#[CoversClass(EntityOptions::class)]
#[CoversClass(ColumnRenderer::class)]
#[CoversClass(RelationRenderer::class)]
#[CoversClass(GeneratedClass::class)]
#[CoversClass(TableSchema::class)]
#[CoversClass(ForeignKeySchema::class)]
#[Group('unit')]
final class EntityGeneratorTest extends TestCase
{
    /**
     * @mago-expect lint:no-literal-namespace-string The generated namespace is data, not a class reference.
     */
    private const string NAMESPACE = 'App\\Model';

    private static function orders(): TableSchema
    {
        return new TableSchema(
            'orders',
            'shop',
            [
                new ColumnSchema('id', 'int', false),
                new ColumnSchema('user_id', 'int', false),
                new ColumnSchema('reviewer_id', 'int', true),
                new ColumnSchema('user', 'varchar', true),
            ],
            ['id'],
            [
                new ForeignKeySchema(['user_id'], 'users', ['id']),
                new ForeignKeySchema(['reviewer_id'], 'users', ['id']),
            ],
        );
    }

    private static function users(): TableSchema
    {
        return new TableSchema(
            'users',
            null,
            [
                new ColumnSchema('id', 'integer', false, null, generated: true),
                new ColumnSchema('email', 'varchar', false),
                new ColumnSchema('active', 'boolean', false, '1'),
                new ColumnSchema('created_at', 'datetime', false),
                new ColumnSchema('meta', 'json', true),
                new ColumnSchema('version', 'int', false, '1'),
            ],
            ['id'],
        );
    }

    #[Test]
    public function compositeForeignKeysListTheirColumns(): void
    {
        $table = new TableSchema(
            'memberships',
            null,
            [
                new ColumnSchema('id', 'int', false),
                new ColumnSchema('group_id', 'int', false),
                new ColumnSchema('site', 'varchar', false),
            ],
            ['id'],
            [new ForeignKeySchema(['group_id', 'site'], 'groups', ['id', 'site'])],
        );

        static::assertStringContainsString(
            "#[BelongsTo(Group::class, foreignKey: ['group_id', 'site'], ownerKey: ['id', 'site'])]",
            (new EntityGenerator())->generate($table)->source,
        );
    }

    #[Test]
    public function compositeKeysGetAnIdPerColumn(): void
    {
        $table = new TableSchema(
            'user_tag',
            null,
            [new ColumnSchema('user_id', 'int', false), new ColumnSchema('tag_id', 'int', false)],
            ['user_id', 'tag_id'],
        );
        $metadata = GeneratedClassLoader::load([(new EntityGenerator(new EntityOptions(self::NAMESPACE)))->generate(
            $table,
        )], self::NAMESPACE)['UserTag'];

        static::assertSame(['user_id', 'tag_id'], $metadata->getIdentifierColumns());
    }

    #[Test]
    public function generatesColumnsWithTypesDefaultsAndRoles(): void
    {
        $generated = (new EntityGenerator(new EntityOptions(self::NAMESPACE, versionColumn: 'version')))->generate(
            self::users(),
        );
        $metadata = GeneratedClassLoader::load([$generated], self::NAMESPACE)['User'];

        static::assertSame(
            [
                'User',
                ['id', 'email', 'active', 'created_at', 'meta', 'version'],
                'id',
                'version',
                DateTimeImmutable::class,
                'json',
            ],
            [
                $generated->className,
                $metadata->getColumnNames(),
                $metadata->generatedIdentifier?->propertyName,
                $metadata->version?->propertyName,
                $metadata->getField('createdAt')->type->phpType,
                $metadata->getField('meta')->type->typeName,
            ],
        );
    }

    #[Test]
    public function relationsCanBeLeftOutAndClassesRenamed(): void
    {
        $generated = (new EntityGenerator(
            new EntityOptions(self::NAMESPACE, ['orders' => 'Purchase'], relations: false),
        ))->generate(self::orders());

        static::assertSame(['Purchase', false], [$generated->className, str_contains($generated->source, 'BelongsTo')]);
    }

    #[Test]
    public function rendersPropertyDefaultsAndUninitialisedColumns(): void
    {
        $source = (new EntityGenerator())->generate(self::users())->source;

        static::assertSame([true, true, true, true], [
            str_contains($source, 'public ?int $id = null;'),
            str_contains($source, 'public string $email;'),
            str_contains($source, 'public bool $active = true;'),
            str_contains($source, 'public ?array $meta = null;'),
        ]);
    }

    #[Test]
    public function turnsForeignKeysIntoLazyBelongsToRelations(): void
    {
        $options   = new EntityOptions(self::NAMESPACE);
        $generator = new EntityGenerator($options);
        $metadata  = GeneratedClassLoader::load([
            $generator->generate(self::users()),
            $generator->generate(self::orders()),
        ], self::NAMESPACE)['Order'];
        $source = $generator->generate(self::orders())->source;

        static::assertSame(
            [['userEntity', 'reviewer'], 'shop', true, true],
            [
                array_keys($metadata->relations),
                $metadata->schema,
                str_contains($source, 'use LazyRelationsTrait;'),
                str_contains($source, 'public ?User $reviewer;'),
            ],
        );
    }
}
