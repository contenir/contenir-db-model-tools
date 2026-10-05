<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

use Contenir\Db\Model\Metadata\FieldMetadata;
use Contenir\Db\Model\Metadata\FieldRole;
use Contenir\Db\Model\Tools\Generator\TypeMapper;
use Contenir\Db\Model\Tools\Schema\ColumnSchema;

use function array_filter;
use function array_values;
use function sprintf;

/**
 * Compares one mapped property with its column: nullability and type.
 *
 * @internal
 */
final readonly class FieldChecks
{
    /**
     * @return list<Issue>
     */
    public static function check(FieldMetadata $field, ColumnSchema $column): array
    {
        return array_values(array_filter([self::nullability($field, $column), self::type($field, $column)]));
    }

    /**
     * Whether an insert must supply the column.
     */
    public static function isRequired(ColumnSchema $column): bool
    {
        return ! $column->nullable && null === $column->default && ! $column->generated;
    }

    private static function nullability(FieldMetadata $field, ColumnSchema $column): ?Issue
    {
        if ($column->nullable && ! $field->type->nullable) {
            return Issue::error(sprintf(
                'Column "%s" allows NULL but property $%s is not nullable',
                $column->name,
                $field->propertyName,
            ));
        }

        if ($field->type->nullable && self::isRequired($column) && FieldRole::Column === $field->role) {
            return Issue::warning(sprintf(
                'Property $%s is nullable but column "%s" is NOT NULL without a default',
                $field->propertyName,
                $column->name,
            ));
        }

        return null;
    }

    private static function type(FieldMetadata $field, ColumnSchema $column): ?Issue
    {
        $columnType = TypeMapper::map($column)->phpType;
        if (TypeCompatibility::accepts($field->type, $columnType)) {
            return null;
        }

        return Issue::warning(sprintf(
            'Property $%s is %s but column "%s" is %s (%s)',
            $field->propertyName,
            (string) $field->type->phpType,
            $column->name,
            $column->dataType,
            $columnType,
        ));
    }
}
