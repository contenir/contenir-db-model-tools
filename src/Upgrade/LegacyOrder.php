<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use function explode;
use function is_array;
use function is_int;
use function is_string;
use function preg_replace;
use function sprintf;
use function strtoupper;
use function trim;

/**
 * Converts a 1.x relation "order" (["created_at DESC"] or
 * ["created_at" => "DESC"]) to a 2.x orderBy map.
 *
 * @internal
 */
final readonly class LegacyOrder
{
    /**
     * @param list<string> $notes receives a note for each entry that cannot be converted
     *
     * @return array<string, 'ASC'|'DESC'>
     *
     * @mago-expect analysis:mixed-assignment 1.x configuration is untyped; each entry is checked.
     */
    public static function convert(string $relation, mixed $order, array &$notes): array
    {
        $orderBy = [];
        foreach (is_array($order) ? $order : [$order] as $key => $value) {
            [$column, $direction] = self::entry($key, $value);
            if ('' !== $column && ('ASC' === $direction || 'DESC' === $direction)) {
                $orderBy[$column] = $direction;

                continue;
            }

            $notes[] = sprintf('relation $%s: order entry "%s" was dropped', $relation, $column);
        }

        return $orderBy;
    }

    /**
     * @return array{string, string} column and upper-case direction
     */
    private static function entry(int|string $key, mixed $value): array
    {
        if (is_int($key)) {
            $text  = trim(is_string($value) ? $value : '');
            $parts = explode(' ', (string) preg_replace('/\s+/', replacement: ' ', subject: $text), limit: 2);

            return [$parts[0], strtoupper($parts[1] ?? 'ASC')];
        }

        return [$key, is_string($value) ? strtoupper(trim($value)) : ''];
    }
}
