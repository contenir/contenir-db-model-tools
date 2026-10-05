<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

use Contenir\Db\Model\Metadata\EntityMetadata;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Schema\SchemaReader;

use function in_array;
use function sprintf;

/**
 * Checks that many-to-many join tables and their columns exist. Owner and
 * target key columns are checked as mapped columns of each entity.
 *
 * @internal
 */
final readonly class RelationChecks
{
    /**
     * @param EntityMetadata<object> $metadata
     *
     * @return list<Issue>
     *
     * @throws ToolException
     */
    public static function check(EntityMetadata $metadata, SchemaReader $reader): array
    {
        $issues = [];
        foreach ($metadata->relations as $relation) {
            $join = $relation->keys->joinTable;
            if (null === $join) {
                continue;
            }

            if (! in_array($join->table, $reader->tableNames($metadata->schema), strict: true)) {
                $issues[] = Issue::error(sprintf(
                    'Join table "%s" of relation $%s does not exist',
                    $join->table,
                    $relation->name,
                ));

                continue;
            }

            $table = $reader->read($join->table, $metadata->schema);
            foreach ([...$join->localColumns, ...$join->targetColumns] as $column) {
                if (null !== $table->column($column)) {
                    continue;
                }

                $issues[] = Issue::error(sprintf(
                    'Join column "%s.%s" of relation $%s does not exist',
                    $join->table,
                    $column,
                    $relation->name,
                ));
            }
        }

        return $issues;
    }
}
