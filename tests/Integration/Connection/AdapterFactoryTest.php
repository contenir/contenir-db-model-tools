<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Integration\Connection;

use Contenir\Db\Model\Tools\Connection\AdapterFactory;
use Contenir\Db\Model\Tools\Exception\ToolException;
use ContenirTest\Db\Model\Tools\TestAsset\Db\Platform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(AdapterFactory::class)]
#[CoversClass(ToolException::class)]
#[Group('integration')]
final class AdapterFactoryTest extends TestCase
{
    /**
     * @return array<string, array{string}>
     */
    public static function invalidDsnProvider(): array
    {
        return [
            'no scheme'          => ['localhost/db'],
            'no host'            => ['mysql:///db'],
            'unsupported scheme' => ['oracle://user:secret@host/db'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function serverDsnProvider(): array
    {
        return [
            'mysql'    => ['mysql://user:s3cret@127.0.0.1:1/db'],
            'pgsql'    => ['pgsql://user:s3cret@127.0.0.1:1/db'],
            'postgres' => ['postgres://user:s3cret@127.0.0.1:1/db'],
        ];
    }

    #[DataProvider('serverDsnProvider')]
    #[Test]
    public function connectionFailuresNeverRevealThePassword(string $dsn): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches('~^Cannot connect to \w+://user:\*\*\*@127\.0\.0\.1:1/db: (?!.*s3cret)~');

        AdapterFactory::fromDsn($dsn);
    }

    #[Test]
    public function connectsToTheTestDatabase(): void
    {
        $platform = Platform::fromEnvironment();
        $adapter  = AdapterFactory::fromDsn($platform->toolsDsn());

        static::assertSame(
            ['sqlite' => 'SQLite', 'mysql' => 'MySQL', 'pgsql' => 'PostgreSQL'][$platform->value],
            $adapter->getPlatform()->getName(),
        );
    }

    #[DataProvider('invalidDsnProvider')]
    #[Test]
    public function rejectsInvalidDsns(string $dsn): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches('~^Cannot parse DSN "[^"]*"; use ~');

        AdapterFactory::fromDsn($dsn);
    }

    #[Test]
    public function reportsSqliteConnectionFailures(): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches('~^Cannot connect to sqlite:///nonexistent/dir/app\.db: ~');

        AdapterFactory::fromDsn('sqlite:///nonexistent/dir/app.db');
    }
}
