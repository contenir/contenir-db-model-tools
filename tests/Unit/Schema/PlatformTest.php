<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Schema;

use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Schema\Platform;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\Platform\PlatformInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Platform::class)]
#[CoversClass(ToolException::class)]
#[Group('unit')]
final class PlatformTest extends TestCase
{
    /**
     * @return array<string, array{string, Platform}>
     */
    public static function platformProvider(): array
    {
        return [
            'SQLite'     => ['SQLite', Platform::Sqlite],
            'MySQL'      => ['MySQL', Platform::Mysql],
            'PostgreSQL' => ['PostgreSQL', Platform::Pgsql],
        ];
    }

    private static function adapterNamed(string $name): AdapterInterface
    {
        $platform = static::createStub(PlatformInterface::class);
        $platform->method('getName')->willReturn($name);
        $adapter = static::createStub(AdapterInterface::class);
        $adapter->method('getPlatform')->willReturn($platform);

        return $adapter;
    }

    #[DataProvider('platformProvider')]
    #[Test]
    public function recognisesAdapterPlatforms(string $name, Platform $expected): void
    {
        static::assertSame($expected, Platform::of(self::adapterNamed($name)));
    }

    #[Test]
    public function rejectsOtherPlatforms(): void
    {
        $this->expectExceptionObject(ToolException::unsupportedPlatform('Oracle'));

        Platform::of(self::adapterNamed('Oracle'));
    }
}
