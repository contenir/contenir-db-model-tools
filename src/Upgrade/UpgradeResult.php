<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

/**
 * An upgraded entity file, and what still needs attention by hand.
 *
 * @api
 */
final readonly class UpgradeResult
{
    /**
     * @param list<string> $notes
     */
    public function __construct(
        public string $className,
        public string $source,
        public array $notes,
    ) {}
}
