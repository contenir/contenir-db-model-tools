<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use PhpParser\Node;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;

use function in_array;
use function sprintf;

/**
 * Finds methods kept from a 1.x entity that still use the 1.x API
 * ($this->data, parent:: calls, populate() and friends), which no longer
 * exists once the class stops extending AbstractEntity.
 *
 * @internal
 */
final readonly class LegacyApiScanner
{
    private const array PROPERTIES = ['data', 'modifiedDataFields', 'events'];

    private const array METHODS = [
        'populate',
        'exchangeArray',
        'getArrayCopy',
        'getModifiedArrayCopy',
        'markClean',
        'getEventManager',
        'getPrimaryKeys',
        'getColumns',
        'getRelations',
        'nextVersion',
    ];

    /**
     * @return list<string> one note per method
     */
    public static function scan(Class_ $class): array
    {
        $notes = [];
        foreach ($class->getMethods() as $method) {
            $uses = (new NodeFinder())->findFirst($method->stmts ?? [], self::isLegacy(...));
            if (null === $uses && ! in_array($method->name->toString(), self::METHODS, strict: true)) {
                continue;
            }

            $notes[] = sprintf('method %s() uses the 1.x entity API; rewrite it for typed properties', $method->name);
        }

        return $notes;
    }

    private static function isLegacy(Node $node): bool
    {
        return match (true) {
            $node instanceof PropertyFetch => self::onThis($node->var) && self::named($node->name, self::PROPERTIES),
            $node instanceof MethodCall => self::onThis($node->var) && self::named($node->name, self::METHODS),
            $node instanceof StaticCall => $node->class instanceof Name && 'parent' === $node->class->toLowerString(),
            default => false,
        };
    }

    /**
     * @param list<string> $names
     */
    private static function named(Node $name, array $names): bool
    {
        return $name instanceof Identifier && in_array($name->toString(), $names, strict: true);
    }

    private static function onThis(Node $var): bool
    {
        return $var instanceof Variable && 'this' === $var->name;
    }
}
