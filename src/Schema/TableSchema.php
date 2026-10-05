<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Schema;

use function in_array;

/**
 * A table as read from a live database.
 *
 * @api
 */
final readonly class TableSchema
{
    /**
     * @param list<ColumnSchema>     $columns
     * @param list<string>           $primaryKey
     * @param list<ForeignKeySchema> $foreignKeys
     */
    public function __construct(
        public string $name,
        public ?string $schema,
        public array $columns,
        public array $primaryKey = [],
        public array $foreignKeys = [],
    ) {}

    public function column(string $name): ?ColumnSchema
    {
        foreach ($this->columns as $column) {
            if ($column->name === $name) {
                return $column;
            }
        }

        return null;
    }

    public function isPrimaryKey(string $column): bool
    {
        return in_array($column, $this->primaryKey, strict: true);
    }
}
