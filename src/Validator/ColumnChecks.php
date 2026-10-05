<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

use Contenir\Db\Model\Metadata\EntityMetadata;
use Contenir\Db\Model\Tools\Schema\TableSchema;

use function array_push;
use function sprintf;

/**
 * Checks every mapped column exists and matches its property, and flags
 * unmapped columns that inserts cannot leave out.
 *
 * @internal
 */
final readonly class ColumnChecks
{
    /**
     * @param EntityMetadata<object> $metadata
     *
     * @return list<Issue>
     */
    public static function check(EntityMetadata $metadata, TableSchema $table): array
    {
        $issues = [];
        foreach ($metadata->fields as $field) {
            $column = $table->column($field->columnName);
            if (null !== $column) {
                array_push($issues, ...FieldChecks::check($field, $column));

                continue;
            }

            $issues[] = Issue::error(sprintf(
                'Column "%s" (property $%s) does not exist',
                $field->columnName,
                $field->propertyName,
            ));
        }

        foreach ($table->columns as $column) {
            if ($metadata->hasColumn($column->name) || ! FieldChecks::isRequired($column)) {
                continue;
            }

            $issues[] = Issue::warning(sprintf(
                'Column "%s" is NOT NULL without a default but is not mapped, so inserts will fail',
                $column->name,
            ));
        }

        return $issues;
    }
}
