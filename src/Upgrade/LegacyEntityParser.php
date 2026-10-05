<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Contenir\Db\Model\Tools\Exception\NotLegacyEntityException;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Exception\UpgradeException;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\Node\Stmt;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\CloningVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

use function in_array;
use function is_array;
use function is_string;

/**
 * Parses a 1.x entity file: the first class declaring $columns or
 * $primaryKeys, and the mapping in its default values.
 *
 * @internal
 */
final readonly class LegacyEntityParser
{
    private Parser $parser;

    public function __construct(
        private RelationConfigReader $relations,
    ) {
        $this->parser = (new ParserFactory())->createForHostVersion();
    }

    /**
     * @param array<Node> $statements
     */
    private static function findClass(array $statements): ?Class_
    {
        foreach ((new NodeFinder())->findInstanceOf($statements, Class_::class) as $class) {
            foreach ($class->getProperties() as $property) {
                foreach ($property->props as $item) {
                    if (in_array($item->name->toString(), ['columns', 'primaryKeys'], strict: true)) {
                        return $class;
                    }
                }
            }
        }

        return null;
    }

    /**
     * @param list<string> $notes receives notes on settings that cannot be carried over
     *
     * @return array{LegacySource, LegacyEntity}
     *
     * @throws ToolException When the file does not parse.
     * @throws NotLegacyEntityException When it holds no 1.x entity.
     * @throws UpgradeException When its mapping is not a constant expression.
     *
     * @mago-expect analysis:mixed-assignment Evaluated defaults are untyped; each is checked before use.
     */
    public function parse(string $file, string $code, array &$notes): array
    {
        try {
            $original = $this->parser->parse($code) ?? [];
        } catch (Error $e) {
            throw ToolException::cannotParse($file, $e->getMessage());
        }

        $traverser = new NodeTraverser(new CloningVisitor(), new NameResolver(options: ['replaceNodes' => false]));
        /** @var list<Stmt> $modified traversal returns the top-level statements it is given */
        $modified  = $traverser->traverse($original);
        $class     = self::findClass($modified) ?? throw NotLegacyEntityException::in($file);
        $values    = LegacyDefaults::of($file, $class);
        $name      = (string) $class->namespacedName?->toString();
        $version   = $values['versionColumn'] ?? null;
        $relations = $values['relations'] ?? null;

        return [
            new LegacySource($original, $modified, $this->parser->getTokens(), $class),
            new LegacyEntity(
                $name,
                StringList::of($values['columns'] ?? []),
                StringList::of($values['primaryKeys'] ?? []),
                is_string($version) && '' !== $version ? $version : null,
                $this->relations->read($name, is_array($relations) ? $relations : [], $notes),
            ),
        ];
    }
}
