<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use function is_array;
use function is_string;

/**
 * Key columns of a 1.x relation: "column", "table.column" and "via".
 *
 * @internal
 */
final readonly class LegacyKeys
{
    /**
     * @param non-empty-list<string> $columns       this entity's columns ("column")
     * @param non-empty-list<string> $targetColumns the target's columns ("table.column", default "column")
     * @param list<string>           $viaColumns    join-table columns matching $columns ("via.column")
     * @param list<string>           $viaJoin       join-table columns matching $targetColumns ("via.join")
     */
    public function __construct(
        public array $columns,
        public array $targetColumns,
        public ?string $viaTable = null,
        public array $viaColumns = [],
        public array $viaJoin = [],
    ) {}

    /**
     * @param array<array-key, mixed> $config the relation's 1.x configuration
     *
     * @return self|null null when "column" is missing
     */
    public static function read(array $config): ?self
    {
        $columns = StringList::of($config['column'] ?? null);
        if ([] === $columns) {
            return null;
        }

        $table         = is_array($config['table'] ?? null) ? $config['table'] : [];
        $targetColumns = StringList::of($table['column'] ?? null);
        $via           = is_array($config['via'] ?? null) ? $config['via'] : [];

        return new self(
            $columns,
            [] === $targetColumns ? $columns : $targetColumns,
            is_string($via['table'] ?? null) ? $via['table'] : null,
            StringList::of($via['column'] ?? null),
            StringList::of($via['join'] ?? null),
        );
    }
}
