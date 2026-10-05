<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Stmt\Nop;
use PhpParser\Node\Stmt\TraitUse;

use function array_slice;

/**
 * Orders an upgraded class body: generated trait uses, the class's own
 * leading constants and trait uses, the generated properties, then the
 * remaining members, without the 1.x mapping properties. Generated members
 * are separated by blank lines.
 *
 * @internal
 */
final readonly class ClassMembers
{
    /**
     * @param array<Stmt> $generated
     * @param array<Stmt> $existing
     *
     * @return list<Stmt>
     */
    public static function merge(array $generated, array $existing): array
    {
        $head       = [];
        $properties = [];
        foreach ($generated as $statement) {
            if ($statement instanceof TraitUse) {
                $head[] = $statement;

                continue;
            }

            $properties[] = $statement;
        }

        $rest    = MappingProperties::strip($existing);
        $leading = 0;
        foreach ($rest as $statement) {
            if (! $statement instanceof ClassConst && ! $statement instanceof TraitUse) {
                break;
            }

            $head[] = $statement;
            $leading++;
        }

        $members = $head;
        foreach ($properties as $property) {
            if ([] !== $members) {
                $members[] = new Nop();
            }

            $members[] = $property;
        }

        $rest = array_slice($rest, offset: $leading);

        return [] === $rest ? $members : [...$members, new Nop(), ...$rest];
    }
}
