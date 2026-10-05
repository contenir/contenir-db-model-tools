<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Connection;

use Contenir\Db\Model\Tools\Exception\ToolException;
use PDO;
use PDOException;
use PhpDb\Adapter\Adapter;
use PhpDb\Mysql;
use PhpDb\Pgsql;
use PhpDb\Sqlite;
use SensitiveParameter;

use function array_key_exists;
use function class_exists;
use function parse_url;
use function preg_replace;
use function rawurldecode;
use function str_starts_with;
use function substr;
use function trim;

/**
 * Builds a phpdb adapter from a DSN URL:
 *
 *     sqlite:///absolute/path/app.db   sqlite:relative/app.db   sqlite::memory:
 *     mysql://user:pass@host:3306/database
 *     pgsql://user:pass@host:5432/database
 *
 * @api
 */
final readonly class AdapterFactory
{
    /**
     * @throws ToolException
     */
    public static function fromDsn(#[SensitiveParameter] string $dsn): Adapter
    {
        if (str_starts_with($dsn, 'sqlite:')) {
            return self::sqlite(substr($dsn, offset: 7));
        }

        $url = parse_url($dsn);
        if (false === $url || ! array_key_exists('scheme', $url) || ! array_key_exists('host', $url)) {
            throw ToolException::invalidDsn(self::redact($dsn));
        }

        $database = trim($url['path'] ?? '', characters: '/');
        $port     = array_key_exists('port', $url) ? ";port={$url['port']}" : '';
        $user     = rawurldecode($url['user'] ?? '');
        $password = rawurldecode($url['pass'] ?? '');

        return match ($url['scheme']) {
            'mysql' => self::mysql(self::connect(
                $dsn,
                "mysql:host={$url['host']}{$port};dbname={$database};charset=utf8mb4",
                $user,
                $password,
            )),
            'pgsql', 'postgres', 'postgresql' => self::pgsql(self::connect(
                $dsn,
                "pgsql:host={$url['host']}{$port};dbname={$database}",
                $user,
                $password,
            )),
            default                           => throw ToolException::invalidDsn(self::redact($dsn)),
        };
    }

    /**
     * @throws ToolException With the DSN's password removed from the message.
     */
    private static function connect(
        #[SensitiveParameter]
        string $dsn,
        string $pdoDsn,
        string $user,
        #[SensitiveParameter]
        string $password,
    ): PDO {
        try {
            return new PDO($pdoDsn, $user, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        } catch (PDOException $e) {
            throw ToolException::connectionFailed(self::redact($dsn), $e->getMessage());
        }
    }

    /**
     * The missing-package branches here are excluded from coverage: every
     * platform package is a dev dependency, so they cannot be reached in tests.
     *
     * @throws ToolException
     */
    private static function mysql(PDO $pdo): Adapter
    {
        if (! class_exists(Mysql\Pdo\Driver::class)) {
            throw ToolException::missingPlatformPackage('MySQL', 'php-db/phpdb-mysql'); // @codeCoverageIgnore
        }

        $driver = new Mysql\Pdo\Driver(new Mysql\Pdo\Connection($pdo));

        return new Adapter($driver, new Mysql\AdapterPlatform($driver));
    }

    /**
     * @throws ToolException
     */
    private static function pgsql(PDO $pdo): Adapter
    {
        if (! class_exists(Pgsql\Pdo\Driver::class)) {
            throw ToolException::missingPlatformPackage('PostgreSQL', 'php-db/phpdb-pgsql'); // @codeCoverageIgnore
        }

        $driver = new Pgsql\Pdo\Driver(new Pgsql\Pdo\Connection($pdo));

        return new Adapter($driver, new Pgsql\AdapterPlatform($driver));
    }

    private static function redact(#[SensitiveParameter] string $dsn): string
    {
        return (string) preg_replace('/:[^:@\/]*@/', replacement: ':***@', subject: $dsn);
    }

    /**
     * @throws ToolException
     */
    private static function sqlite(string $path): Adapter
    {
        if (! class_exists(Sqlite\Pdo\Driver::class)) {
            throw ToolException::missingPlatformPackage('SQLite', 'php-db/phpdb-sqlite'); // @codeCoverageIgnore
        }

        $file   = str_starts_with($path, '//') ? substr($path, offset: 2) : $path;
        $driver = new Sqlite\Pdo\Driver(new Sqlite\Pdo\Connection(self::connect(
            "sqlite:{$path}",
            "sqlite:{$file}",
            '',
            '',
        )));

        return new Adapter($driver, new Sqlite\AdapterPlatform($driver));
    }
}
