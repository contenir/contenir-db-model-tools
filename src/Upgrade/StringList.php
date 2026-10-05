<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use function array_values;
use function is_array;
use function is_string;

/**
 * Reads a 1.x setting that may be one string or a list of strings.
 *
 * @internal
 */
final readonly class StringList
{
    /**
     * @return list<string> non-empty strings, in order
     *
     * @mago-expect analysis:mixed-assignment 1.x configuration is untyped; each entry is checked.
     */
    public static function of(mixed $value): array
    {
        $strings = [];
        foreach (is_array($value) ? array_values($value) : [$value] as $item) {
            if (! is_string($item) || '' === $item) {
                continue;
            }

            $strings[] = $item;
        }

        return $strings;
    }
}
