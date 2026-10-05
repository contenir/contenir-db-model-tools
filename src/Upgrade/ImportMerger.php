<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Closure;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Declare_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\Use_;

use function array_splice;
use function array_values;
use function count;

/**
 * Rewrites a file's imports, in its namespace or at the top level: drops
 * unwanted ones and adds new ones that are not already imported, after
 * the existing imports (or any leading declare statements, followed by a
 * blank line).
 *
 * @internal
 */
final readonly class ImportMerger
{
    /**
     * @param array<Stmt>           $statements the file's top-level statements
     * @param array<Use_>           $imports    imports to add
     * @param Closure(string): bool $removed    whether an imported name is dropped
     *
     * @return list<Stmt> the file's new top-level statements
     */
    public static function merge(array $statements, array $imports, Closure $removed): array
    {
        foreach ($statements as $statement) {
            if (! $statement instanceof Namespace_) {
                continue;
            }

            $statement->stmts = self::rewrite($statement->stmts, $imports, $removed);

            return array_values($statements);
        }

        return self::rewrite($statements, $imports, $removed);
    }

    /**
     * @param array<Stmt>           $statements
     * @param array<Use_>           $imports
     * @param Closure(string): bool $removed
     *
     * @return list<Stmt>
     */
    private static function rewrite(array $statements, array $imports, Closure $removed): array
    {
        $kept     = [];
        $existing = [];
        $position = 0;
        foreach ($statements as $statement) {
            $statement = $statement instanceof Use_
                ? ImportFilter::without($statement, $removed, $existing)
                : $statement;
            if (null === $statement) {
                continue;
            }

            $leading  = $statement instanceof Declare_ && count($kept) === $position;
            $kept[]   = $statement;
            $position = $statement instanceof Use_ || $leading ? count($kept) : $position;
        }

        $added = ImportFilter::missing($imports, $existing);
        if ([] === $existing && [] !== $added) {
            $added = 0 === $position ? [...$added, new Nop()] : [new Nop(), ...$added];
        }

        array_splice($kept, offset: $position, length: 0, replacement: $added);

        return $kept;
    }
}
