<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Closure;
use PhpParser\Node\Stmt\Use_;

use function in_array;

/**
 * Filters import statements by imported name.
 *
 * @internal
 */
final readonly class ImportFilter
{
    /**
     * The imports whose names are not already imported.
     *
     * @param array<Use_>  $imports
     * @param list<string> $existing
     *
     * @return list<Use_>
     */
    public static function missing(array $imports, array $existing): array
    {
        $missing = [];
        foreach ($imports as $import) {
            foreach ($import->uses as $use) {
                if (in_array($use->name->toString(), $existing, strict: true)) {
                    continue;
                }

                $missing[] = $import;
            }
        }

        return $missing;
    }

    /**
     * The statement without the names $removed accepts, or null when none remain.
     *
     * @param Closure(string): bool $removed
     * @param list<string>          $existing receives the names still imported
     */
    public static function without(Use_ $statement, Closure $removed, array &$existing): ?Use_
    {
        $uses = [];
        foreach ($statement->uses as $use) {
            $name = $use->name->toString();
            if ($removed($name)) {
                continue;
            }

            $uses[]     = $use;
            $existing[] = $name;
        }

        if ([] === $uses) {
            return null;
        }

        $statement->uses = $uses;

        return $statement;
    }
}
