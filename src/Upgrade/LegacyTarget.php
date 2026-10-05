<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

/**
 * A 1.x relation's target: the repository it named, and the entity class
 * that repository served.
 *
 * @internal
 */
final readonly class LegacyTarget
{
    public function __construct(
        public string $entity,
        public string $repository,
    ) {}
}
