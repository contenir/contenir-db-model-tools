<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Schema;

use PhpDb\Metadata\Object\ConstraintObject;

use function array_values;

/**
 * Primary and foreign keys from phpdb constraint objects.
 *
 * @internal
 */
final readonly class Constraints
{
    /**
     * @param list<ConstraintObject> $constraints
     *
     * @return list<ForeignKeySchema>
     */
    public static function foreignKeys(array $constraints): array
    {
        $foreignKeys = [];
        foreach ($constraints as $constraint) {
            if (! $constraint->isForeignKey()) {
                continue;
            }

            $foreignKeys[] = new ForeignKeySchema(
                array_values($constraint->getColumns()),
                (string) $constraint->getReferencedTableName(),
                array_values($constraint->getReferencedColumns() ?? []),
            );
        }

        return $foreignKeys;
    }

    /**
     * @param list<ConstraintObject> $constraints
     *
     * @return list<string>
     */
    public static function primaryKey(array $constraints): array
    {
        foreach ($constraints as $constraint) {
            if ($constraint->isPrimaryKey()) {
                return array_values($constraint->getColumns());
            }
        }

        return [];
    }
}
