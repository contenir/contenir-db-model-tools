<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Trait;

use ContenirTest\Db\Model\Tools\TestAsset\Db\Platform;
use ContenirTest\Db\Model\Tools\TestAsset\Db\Schema;
use PDO;
use PhpDb\Adapter\Adapter;
use PhpDb\Mysql;
use PhpDb\Pgsql;
use PhpDb\Sqlite;

/**
 * Builds the fixture schema on the platform named by DB_PLATFORM (SQLite
 * by default, or MySQL / PostgreSQL from the DB_* variables). Every call
 * starts from a freshly created schema. Call
 * {@see self::setUpTestDatabase()} from setUp().
 */
trait TestDatabaseTrait
{
    protected Adapter $adapter;

    protected Platform $platform;

    /**
     * @param class-string<Sqlite\AdapterPlatform|Mysql\AdapterPlatform|Pgsql\AdapterPlatform> $platform
     */
    private static function adapter(
        Sqlite\Pdo\Driver|Mysql\Pdo\Driver|Pgsql\Pdo\Driver $driver,
        string $platform,
    ): Adapter {
        return new Adapter($driver, new $platform($driver));
    }

    protected function setUpTestDatabase(): void
    {
        $this->platform = Platform::fromEnvironment();

        $pdo = new PDO($this->platform->pdoDsn(), Platform::user(), Platform::password(), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        foreach (Schema::create($this->platform) as $statement) {
            $pdo->exec($statement);
        }

        $this->adapter = match ($this->platform) {
            Platform::Sqlite => self::adapter(
                new Sqlite\Pdo\Driver(new Sqlite\Pdo\Connection($pdo)),
                Sqlite\AdapterPlatform::class,
            ),
            Platform::Mysql => self::adapter(
                new Mysql\Pdo\Driver(new Mysql\Pdo\Connection($pdo)),
                Mysql\AdapterPlatform::class,
            ),
            Platform::Pgsql => self::adapter(
                new Pgsql\Pdo\Driver(new Pgsql\Pdo\Connection($pdo)),
                Pgsql\AdapterPlatform::class,
            ),
        };
    }
}
