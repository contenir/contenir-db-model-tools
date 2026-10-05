<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use function is_array;
use function is_scalar;
use function is_string;
use function sprintf;

/**
 * A 1.x relation's "where" and "order", converted to 2.x where and
 * orderBy maps.
 *
 * @internal
 */
final readonly class LegacyCriteria
{
    /**
     * @param array<string, scalar|null>  $where   equality conditions on target columns
     * @param array<string, 'ASC'|'DESC'> $orderBy target column => direction
     */
    public function __construct(
        public array $where = [],
        public array $orderBy = [],
    ) {}

    /**
     * @param array<array-key, mixed> $config the relation's 1.x configuration
     * @param list<string>            $notes  receives a note for each setting that cannot be converted
     *
     * @mago-expect analysis:mixed-assignment 1.x configuration is untyped; each entry is checked.
     */
    public static function read(string $relation, array $config, array &$notes): self
    {
        $where = [];
        $given = $config['where'] ?? [];
        foreach (is_array($given) ? $given : [$given] as $key => $value) {
            if (is_string($key) && (null === $value || is_scalar($value))) {
                $where[$key] = $value;

                continue;
            }

            $notes[] = sprintf(
                'relation $%s: where condition %s was dropped; 2.x supports column => value equality only',
                $relation,
                is_string($value) ? "\"{$value}\"" : "\"{$key}\"",
            );
        }

        return new self($where, LegacyOrder::convert($relation, $config['order'] ?? [], $notes));
    }

    public function isEmpty(): bool
    {
        return [] === $this->where && [] === $this->orderBy;
    }
}
