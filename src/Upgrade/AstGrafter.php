<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Use_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;

use function in_array;
use function sprintf;
use function str_starts_with;

/**
 * Grafts a rendered 2.x mapping into a 1.x entity's syntax tree: drops the
 * AbstractEntity base, EntityInterface, the 1.x mapping properties and the
 * imports only they used; adds #[Table], the mapped properties (after any
 * constants) and their imports. Every other member is left as it was.
 *
 * @internal
 */
final readonly class AstGrafter
{
    /** Namespace of 1.x's entity base classes, which 2.x does not have. */
    private const string LEGACY_NAMESPACE = 'Contenir\\Db\\Model\\Entity\\';

    /**
     * @param string       $mapped       rendered by {@see MappedClassBuilder}
     * @param list<string> $repositories 1.x repository classes the relations named, whose imports are dropped
     * @param list<string> $notes        receives notes on what still needs attention
     */
    public static function graft(LegacySource $source, string $mapped, array $repositories, array &$notes): string
    {
        $generated = (new ParserFactory())->createForHostVersion()
            ->parse($mapped) ?? [];
        $generated = (new NodeTraverser(new PositionStripper()))->traverse($generated);
        $template  = (new NodeFinder())->findFirstInstanceOf($generated, Class_::class);
        $class     = $source->class;

        $class->attrGroups = [...($template->attrGroups ?? []), ...$class->attrGroups];
        $class->stmts      = ClassMembers::merge($template->stmts ?? [], $class->stmts);
        $class->implements = self::withoutLegacy($class->implements);
        $notes             = [...$notes, ...self::unextend($class)];

        return $source->withStatements(ImportMerger::merge(
            $source->modified,
            (new NodeFinder())->findInstanceOf($generated, Use_::class),
            static fn(string $name): bool => (
                str_starts_with($name, self::LEGACY_NAMESPACE)
                || in_array($name, $repositories, strict: true)
            ),
        ))->print();
    }

    /**
     * @mago-expect analysis:mixed-assignment Node attributes are untyped; the name is checked before use.
     */
    private static function isLegacy(Name $name): bool
    {
        $resolved = $name->getAttribute('resolvedName');

        return $resolved instanceof Name && str_starts_with($resolved->toString(), self::LEGACY_NAMESPACE);
    }

    /**
     * Drops a 1.x base class; any other parent is kept and noted.
     *
     * @return list<string>
     */
    private static function unextend(Class_ $class): array
    {
        if (null === $class->extends) {
            return [];
        }

        if (self::isLegacy($class->extends)) {
            $class->extends = null;

            return [];
        }

        return [sprintf('still extends %s; upgrade that class too, or remove it', $class->extends->toString())];
    }

    /**
     * @param array<Name> $names
     *
     * @return list<Name>
     */
    private static function withoutLegacy(array $names): array
    {
        $kept = [];
        foreach ($names as $name) {
            if (self::isLegacy($name)) {
                continue;
            }

            $kept[] = $name;
        }

        return $kept;
    }
}
