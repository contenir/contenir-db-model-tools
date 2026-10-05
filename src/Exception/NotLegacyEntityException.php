<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Exception;

use RuntimeException;

use function sprintf;

/**
 * A file holds no 1.x entity, so there is nothing to upgrade.
 *
 * @api
 */
final class NotLegacyEntityException extends RuntimeException implements ExceptionInterface
{
    public static function in(string $file): self
    {
        return new self(sprintf(
            '%s does not contain a 1.x entity (a class declaring $columns or $primaryKeys)',
            $file,
        ));
    }
}
