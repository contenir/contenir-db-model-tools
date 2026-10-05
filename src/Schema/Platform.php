<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Schema;

use Contenir\Db\Model\Tools\Exception\ToolException;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Mysql\Metadata\Source as MysqlSource;
use PhpDb\Pgsql\Metadata\Source as PgsqlSource;
use PhpDb\Sqlite\Metadata\Source as SqliteSource;

use function class_exists;
use function strtolower;

/**
 * The database platforms the tools can read, and their phpdb metadata
 * sources (each from its own php-db platform package).
 *
 * @api
 */
enum Platform
{
    case Sqlite;
    case Mysql;
    case Pgsql;

    /**
     * @throws ToolException
     */
    public static function of(AdapterInterface $adapter): self
    {
        return match (strtolower($adapter->getPlatform()->getName())) {
            'sqlite'     => self::Sqlite,
            'mysql'      => self::Mysql,
            'postgresql' => self::Pgsql,
            default      => throw ToolException::unsupportedPlatform($adapter->getPlatform()->getName()),
        };
    }

    /**
     * @throws ToolException When the platform's phpdb package is not installed.
     */
    public function metadata(AdapterInterface&SchemaAwareInterface $adapter): MetadataInterface
    {
        return match ($this) {
            self::Sqlite => class_exists(SqliteSource::class)
                ? new SqliteSource($adapter)
                : throw ToolException::missingPlatformPackage('SQLite', 'php-db/phpdb-sqlite'),
            self::Mysql => class_exists(MysqlSource::class)
                ? new MysqlSource($adapter)
                : throw ToolException::missingPlatformPackage('MySQL', 'php-db/phpdb-mysql'),
            self::Pgsql => class_exists(PgsqlSource::class)
                ? new PgsqlSource($adapter)
                : throw ToolException::missingPlatformPackage('PostgreSQL', 'php-db/phpdb-pgsql'),
        };
    }
}
