<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Schema;

/**
 * One column as read from a live database.
 *
 * @api
 */
final readonly class ColumnSchema
{
    /**
     * @param string $dataType lower-case type without length, e.g. "varchar", "int", "boolean"
     */
    public function __construct(
        public string $name,
        public string $dataType,
        public bool $nullable,
        public string|int|bool|null $default = null,
        public bool $generated = false,
    ) {}
}
