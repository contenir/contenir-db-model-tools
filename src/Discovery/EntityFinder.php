<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Discovery;

use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Tools\Exception\ToolException;
use PhpParser\Error;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;

use function array_push;
use function file_get_contents;

/**
 * Finds classes carrying #[Table] in a directory tree by parsing the
 * source, without loading any class.
 *
 * @api
 */
final readonly class EntityFinder
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())->createForHostVersion();
    }

    private static function hasTable(Class_ $class): bool
    {
        foreach ($class->attrGroups as $group) {
            foreach ($group->attrs as $attribute) {
                if (Table::class === $attribute->name->toString()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * @return list<string> fully-qualified class names, in file order
     *
     * @throws ToolException When the directory cannot be read or a file cannot be parsed.
     */
    public function find(string $directory): array
    {
        $classes = [];
        foreach (PhpFiles::in($directory) as $file) {
            array_push($classes, ...$this->entitiesIn($file));
        }

        return $classes;
    }

    /**
     * @return list<string>
     *
     * @throws ToolException
     */
    private function entitiesIn(string $file): array
    {
        try {
            $statements = $this->parser->parse((string) file_get_contents($file)) ?? [];
        } catch (Error $e) {
            throw ToolException::cannotParse($file, $e->getMessage());
        }

        $classes = [];
        $nodes   = (new NodeFinder())->findInstanceOf(
            (new NodeTraverser(new NameResolver()))->traverse($statements),
            Class_::class,
        );
        foreach ($nodes as $node) {
            $name = $node->namespacedName?->toString();
            if (null === $name || ! self::hasTable($node)) {
                continue;
            }

            $classes[] = $name;
        }

        return $classes;
    }
}
