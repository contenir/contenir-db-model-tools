<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Generator;

use Contenir\Db\Model\Tools\Generator\PropertyType;
use Contenir\Db\Model\Tools\Generator\TypeMapper;
use Contenir\Db\Model\Tools\Schema\ColumnSchema;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(TypeMapper::class)]
#[CoversClass(PropertyType::class)]
#[CoversClass(ColumnSchema::class)]
#[Group('unit')]
final class TypeMapperTest extends TestCase
{
    /**
     * @return array<string, array{string, string|int|bool|null, int|float|bool|null}>
     */
    public static function defaultProvider(): array
    {
        return [
            'int literal'       => ['int', '5', 5],
            'int from int'      => ['int', 7, 7],
            'int not literal'   => ['int', 'nextval(seq)', null],
            'int decimal text'  => ['int', '1.5', null],
            'float'             => ['real', '1.5', 1.5],
            'float not numeric' => ['real', 'pi()', null],
            'bool one'          => ['boolean', '1', true],
            'bool true'         => ['boolean', 'TRUE', true],
            'bool mysql bit'    => ['boolean', "b'0'", false],
            'bool pg false'     => ['boolean', 'false', false],
            'bool unknown'      => ['boolean', 'maybe', null],
            'string ignored'    => ['varchar', "'draft'", null],
            'no default'        => ['int', null, null],
            'empty default'     => ['int', '', null],
        ];
    }

    /**
     * @return array<string, array{string, string, string|null}>
     */
    public static function typeProvider(): array
    {
        return [
            'int'         => ['int', 'int', null],
            'bigint'      => ['bigint', 'int', null],
            'serial'      => ['serial', 'int', null],
            'double'      => ['double precision', 'float', null],
            'boolean'     => ['boolean', 'bool', null],
            'datetime'    => ['datetime', DateTimeImmutable::class, null],
            'timestamptz' => ['timestamptz', DateTimeImmutable::class, null],
            'date'        => ['date', DateTimeImmutable::class, 'date'],
            'jsonb'       => ['jsonb', 'array', 'json'],
            'decimal'     => ['decimal', 'string', null],
            'varchar'     => ['varchar', 'string', null],
            'unknown'     => ['geometry', 'string', null],
        ];
    }

    #[DataProvider('defaultProvider')]
    #[Test]
    public function convertsLiteralDefaults(
        string $dataType,
        string|int|bool|null $default,
        int|float|bool|null $expected,
    ): void {
        $column = new ColumnSchema('c', $dataType, false, $default);

        static::assertSame($expected, TypeMapper::defaultValue($column, TypeMapper::map($column)));
    }

    #[DataProvider('typeProvider')]
    #[Test]
    public function mapsDatabaseTypesToPropertyTypes(string $dataType, string $phpType, ?string $converter): void
    {
        static::assertEquals(
            new PropertyType($phpType, $converter),
            TypeMapper::map(new ColumnSchema('c', $dataType, false)),
        );
    }
}
