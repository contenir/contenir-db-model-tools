<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Generator;

use Contenir\Db\Model\Tools\Schema\ColumnSchema;
use DateTimeImmutable;

use function in_array;
use function is_numeric;
use function strtolower;

/**
 * Maps database column types to PHP property types. Exact numerics
 * (decimal, numeric, money) map to string so no precision is lost;
 * unknown types map to string.
 *
 * @api
 */
final readonly class TypeMapper
{
    private const array INTEGER = [
        'int',
        'integer',
        'smallint',
        'mediumint',
        'bigint',
        'tinyint',
        'serial',
        'bigserial',
        'smallserial',
        'int2',
        'int4',
        'int8',
    ];

    private const array FLOAT = ['float', 'double', 'double precision', 'real', 'float4', 'float8'];

    private const array BOOLEAN = ['boolean', 'bool', 'bit'];

    private const array DATETIME = [
        'datetime',
        'timestamp',
        'timestamp without time zone',
        'timestamp with time zone',
        'timestamptz',
    ];

    private const array JSON = ['json', 'jsonb'];

    /**
     * A PHP default for int, float and bool columns whose database default
     * is a plain literal; null otherwise (string and date defaults are left
     * to the database by leaving the property uninitialised).
     */
    public static function defaultValue(ColumnSchema $column, PropertyType $type): int|float|bool|null
    {
        $default = $column->default;
        if (null === $default || '' === $default) {
            return null;
        }

        return match ($type->phpType) {
            'int'   => is_numeric($default) && (string) (int) $default === (string) $default ? (int) $default : null,
            'float' => is_numeric($default) ? (float) $default : null,
            'bool'  => self::boolean($default),
            default => null,
        };
    }

    public static function map(ColumnSchema $column): PropertyType
    {
        $type = $column->dataType;

        return match (true) {
            in_array($type, self::INTEGER, strict: true) => new PropertyType('int'),
            in_array($type, self::FLOAT, strict: true) => new PropertyType('float'),
            in_array($type, self::BOOLEAN, strict: true) => new PropertyType('bool'),
            in_array($type, self::DATETIME, strict: true) => new PropertyType(DateTimeImmutable::class),
            'date' === $type => new PropertyType(DateTimeImmutable::class, 'date'),
            in_array($type, self::JSON, strict: true) => new PropertyType('array', 'json'),
            default => new PropertyType('string'),
        };
    }

    private static function boolean(string|int|bool $value): ?bool
    {
        return match (strtolower((string) $value)) {
            '1', 'true', "b'1'", 't'  => true,
            '0', 'false', "b'0'", 'f' => false,
            default                   => null,
        };
    }
}
