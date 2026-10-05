<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

/**
 * How 1.x entities are upgraded.
 *
 * @api
 */
final readonly class UpgradeOptions
{
    /**
     * @param string|null           $table       the table, when it cannot be read from the 1.x repository
     * @param bool                  $columnNames keep column names as property names, as 1.x accessed them
     * @param array<string, string> $targets     1.x repository class => entity class, for relation targets
     */
    public function __construct(
        public ?string $table = null,
        public ?string $schema = null,
        public bool $columnNames = true,
        public array $targets = [],
    ) {}
}
