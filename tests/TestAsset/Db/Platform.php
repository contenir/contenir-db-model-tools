<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Db;

use function getenv;
use function is_string;
use function rawurlencode;

/**
 * Database the integration suite runs against, chosen with the
 * DB_PLATFORM environment variable (default: in-memory SQLite).
 */
enum Platform: string
{
    case Sqlite = 'sqlite';
    case Mysql  = 'mysql';
    case Pgsql  = 'pgsql';

    public static function env(string $name, string $default): string
    {
        $value = getenv($name);

        return is_string($value) && '' !== $value ? $value : $default;
    }

    public static function fromEnvironment(): self
    {
        return self::from(self::env('DB_PLATFORM', 'sqlite'));
    }

    public static function password(): string
    {
        return self::env('DB_PASSWORD', '');
    }

    public static function user(): string
    {
        return self::env('DB_USER', 'root');
    }

    public function pdoDsn(): string
    {
        $host = self::env('DB_HOST', '127.0.0.1');
        $name = self::env('DB_NAME', 'contenir_test');

        return match ($this) {
            self::Sqlite => 'sqlite::memory:',
            self::Mysql => "mysql:host={$host};port={$this->port()};dbname={$name};charset=utf8mb4",
            self::Pgsql => "pgsql:host={$host};port={$this->port()};dbname={$name}",
        };
    }

    public function port(): string
    {
        return self::env('DB_PORT', Platform::Mysql === $this ? '3306' : '5432');
    }

    /**
     * The same database as a tools DSN URL (an empty database for SQLite).
     */
    public function toolsDsn(): string
    {
        if (self::Sqlite === $this) {
            return 'sqlite::memory:';
        }

        $user     = rawurlencode(self::user());
        $password = rawurlencode(self::password());
        $host     = self::env('DB_HOST', '127.0.0.1');
        $name     = self::env('DB_NAME', 'contenir_test');

        return "{$this->value}://{$user}:{$password}@{$host}:{$this->port()}/{$name}";
    }
}
