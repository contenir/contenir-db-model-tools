<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Tools\Exception\UpgradeException;
use Contenir\Db\Model\Tools\Schema\ColumnSchema;
use Contenir\Db\Model\Tools\Schema\TableSchema;
use Contenir\Db\Model\Tools\Upgrade\LegacyEntity;
use Contenir\Db\Model\Tools\Upgrade\LegacyRelationRenderer;
use Contenir\Db\Model\Tools\Upgrade\MappedClassBuilder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function str_contains;

/**
 * @mago-expect lint:no-literal-namespace-string Class names here are 1.x names that no longer exist.
 */
#[CoversClass(MappedClassBuilder::class)]
#[CoversClass(LegacyRelationRenderer::class)]
#[CoversClass(LegacyEntity::class)]
#[CoversClass(UpgradeException::class)]
#[Group('unit')]
final class MappedClassBuilderTest extends TestCase
{
    private static function table(): TableSchema
    {
        return new TableSchema(
            'users',
            'crm',
            [
                new ColumnSchema('id', 'int', false, null, generated: true),
                new ColumnSchema('email', 'varchar', false),
            ],
            ['id'],
        );
    }

    #[Test]
    public function mapsTheWholeTableWhenNoColumnsAreListed(): void
    {
        $notes = [];
        $code  = (new MappedClassBuilder())->build(
            new LegacyEntity('App\\UserEntity', [], [], null, []),
            self::table(),
            $notes,
        );

        static::assertSame(
            [true, true, true],
            [
                str_contains($code, "#[Table('users', schema: 'crm')]"),
                str_contains($code, "#[Id(generated: true)]\n    public ?int \$id = null;"),
                str_contains($code, "#[Column]\n    public string \$email;"),
            ],
        );
    }

    #[Test]
    public function rejectsListedColumnsTheTableLacks(): void
    {
        $notes = [];

        $this->expectExceptionObject(UpgradeException::columnNotFound('App\\UserEntity', 'name', 'users'));

        (new MappedClassBuilder())->build(
            new LegacyEntity('App\\UserEntity', ['id', 'name'], ['id'], null, []),
            self::table(),
            $notes,
        );
    }
}
