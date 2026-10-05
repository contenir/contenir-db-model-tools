<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Exception;

use RuntimeException;

use function sprintf;

/**
 * A 1.x entity that cannot be upgraded automatically.
 *
 * @api
 */
final class UpgradeException extends RuntimeException implements ExceptionInterface
{
    public static function columnNotFound(string $class, string $column, string $table): self
    {
        return new self(sprintf('%s lists column "%s", which table "%s" does not have', $class, $column, $table));
    }

    public static function invalidMap(string $pair): self
    {
        return new self(sprintf('--map "%s" must be RepositoryClass=EntityClass', $pair));
    }

    public static function invalidRelation(string $class, string $relation, string $reason): self
    {
        return new self(sprintf('%s relation "%s" cannot be upgraded: %s', $class, $relation, $reason));
    }

    public static function tableUnknown(string $class): self
    {
        return new self(sprintf(
            'Cannot find the table for %s from its 1.x repository; pass --table',
            $class,
        ));
    }

    public static function unsupportedExpression(string $file, string $property, string $reason): self
    {
        return new self(sprintf('%s: $%s must be a constant expression (%s)', $file, $property, $reason));
    }
}
