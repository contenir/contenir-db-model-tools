<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Property;

use function in_array;

/**
 * Removes a 1.x entity's mapping properties from its class body.
 *
 * @internal
 */
final readonly class MappingProperties
{
    /**
     * @param array<Stmt> $statements
     *
     * @return list<Stmt> the statements without the 1.x mapping properties
     */
    public static function strip(array $statements): array
    {
        $kept = [];
        foreach ($statements as $statement) {
            if ($statement instanceof Property) {
                $items = [];
                foreach ($statement->props as $item) {
                    if (in_array($item->name->toString(), LegacyDefaults::PROPERTIES, strict: true)) {
                        continue;
                    }

                    $items[] = $item;
                }

                if ([] === $items) {
                    continue;
                }

                $statement->props = $items;
            }

            $kept[] = $statement;
        }

        return $kept;
    }
}
