<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Generator;

use function array_map;
use function count;
use function explode;
use function implode;
use function lcfirst;
use function preg_replace;
use function str_ends_with;
use function strlen;
use function strtolower;
use function substr;
use function ucfirst;

/**
 * Naming conventions for generated code: tables become singular PascalCase
 * class names, columns become camelCase properties.
 *
 * @api
 */
final readonly class Inflector
{
    private const array SINGULAR = [
        'ies'  => 'y',
        'sses' => 'ss',
        'shes' => 'sh',
        'ches' => 'ch',
        'xes'  => 'x',
        'uses' => 'us',
    ];

    /**
     * "order_items" becomes "OrderItem"; "people" stays "People" (pass --class for irregular plurals).
     */
    public static function className(string $table): string
    {
        return ucfirst(self::camel(self::singular($table)));
    }

    /**
     * "created_at" becomes "createdAt".
     */
    public static function property(string $column): string
    {
        return self::camel($column);
    }

    /**
     * Relation property for a foreign key: "author_id" becomes "author";
     * other names fall back to the referenced table, singular.
     *
     * @param list<string> $columns
     */
    public static function relation(array $columns, string $referencedTable): string
    {
        $single = 1 === count($columns) ? $columns[0] : '';
        $base   = str_ends_with(strtolower($single), '_id')
            ? substr($single, offset: 0, length: -3)
            : self::singular($referencedTable);

        return self::camel($base);
    }

    private static function camel(string $name): string
    {
        $parts = explode('_', (string) preg_replace('/[^A-Za-z0-9_]+/', replacement: '_', subject: $name));

        return lcfirst(implode('', array_map(static fn(string $part): string => ucfirst(strtolower($part)), $parts)));
    }

    private static function singular(string $word): string
    {
        $lower = strtolower($word);
        foreach (self::SINGULAR as $plural => $singular) {
            if (str_ends_with($lower, $plural)) {
                return substr($word, offset: 0, length: -strlen($plural)) . $singular;
            }
        }

        $endsInS = str_ends_with($lower, 's') && ! str_ends_with($lower, 'ss') && ! str_ends_with($lower, 'is');

        return $endsInS ? substr($word, offset: 0, length: -1) : $word;
    }
}
