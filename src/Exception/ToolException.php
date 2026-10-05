<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Exception;

use RuntimeException;

use function sprintf;

/**
 * Raised for problems the tools report to the user: bad connection
 * strings, unsupported platforms, missing tables or files.
 *
 * @api
 */
final class ToolException extends RuntimeException implements ExceptionInterface
{
    public static function cannotWrite(string $path, string $reason): self
    {
        return new self(sprintf('Cannot write %s: %s', $path, $reason));
    }

    public static function connectionFailed(string $dsn, string $reason): self
    {
        return new self(sprintf('Cannot connect to %s: %s', $dsn, $reason));
    }

    public static function fileExists(string $path): self
    {
        return new self(sprintf('%s already exists; pass --force to overwrite it', $path));
    }

    public static function invalidDsn(string $dsn): self
    {
        return new self(sprintf(
            'Cannot parse DSN "%s"; use sqlite:///path/to.db, sqlite::memory:, mysql://user:pass@host:port/db or pgsql://user:pass@host:port/db',
            $dsn,
        ));
    }

    public static function missingPlatformPackage(string $platform, string $package): self
    {
        return new self(sprintf(
            'Reading %s schemas needs %s; composer require --dev %s',
            $platform,
            $package,
            $package,
        ));
    }

    public static function noConnection(): self
    {
        return new self('No database connection: pass --dsn, or run through laminas-cli with an adapter configured');
    }

    public static function tableNotFound(string $table): self
    {
        return new self(sprintf('Table "%s" does not exist', $table));
    }

    public static function unsupportedPlatform(string $platform): self
    {
        return new self(sprintf('Platform "%s" is not supported; use SQLite, MySQL or PostgreSQL', $platform));
    }
}
