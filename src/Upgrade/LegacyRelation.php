<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

/**
 * One entry of a 1.x entity's $relations.
 *
 * @internal
 */
final readonly class LegacyRelation
{
    public function __construct(
        public string $name,
        public bool $single,
        public LegacyTarget $target,
        public LegacyKeys $keys,
        public LegacyCriteria $criteria = new LegacyCriteria(),
    ) {}
}
