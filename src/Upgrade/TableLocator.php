<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use PhpParser\Error;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Scalar\String_;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

use function basename;
use function dirname;
use function file_get_contents;
use function is_file;
use function str_replace;
use function strrpos;
use function substr;

/**
 * Finds a 1.x entity's table in the $table of its conventionally paired
 * repository (…/Entity/UserEntity.php → …/Repository/UserRepository.php).
 *
 * @internal
 */
final readonly class TableLocator
{
    public static function find(string $entityFile, string $entityClass): ?string
    {
        $directory = dirname($entityFile);
        $segment   = strrpos($directory, needle: '/Entity');
        if (false === $segment) {
            return null;
        }

        $repository = TargetResolver::repositoryFor($entityClass);
        $file       = substr($directory, offset: 0, length: $segment)
        . '/Repository'
        . substr($directory, offset: $segment + 7)
        . '/'
        . basename(str_replace(
            search: '\\',
            replace: '/',
            subject: $repository,
        ))
        . '.php';

        return is_file($file) ? self::table((string) file_get_contents($file)) : null;
    }

    /**
     * A string, or the first string of an array (['alias' => 'table']).
     */
    private static function literal(mixed $default): ?string
    {
        if ($default instanceof String_) {
            return $default->value;
        }

        $first = $default instanceof Array_ ? $default->items[0]->value ?? null : null;

        return $first instanceof String_ ? $first->value : null;
    }

    private static function table(string $code): ?string
    {
        try {
            $statements = (new ParserFactory())->createForHostVersion()
                ->parse($code) ?? [];
        } catch (Error) {
            return null;
        }

        foreach ((new NodeFinder())->findInstanceOf($statements, Property::class) as $property) {
            foreach ($property->props as $item) {
                if ('table' === $item->name->toString()) {
                    return self::literal($item->default);
                }
            }
        }

        return null;
    }
}
