<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Schema;

/**
 * @api
 */
final readonly class ForeignKeySchema
{
    /**
     * @param list<string> $columns           columns on the owning table
     * @param list<string> $referencedColumns columns on the referenced table, paired by position
     */
    public function __construct(
        public array $columns,
        public string $referencedTable,
        public array $referencedColumns,
    ) {}
}
