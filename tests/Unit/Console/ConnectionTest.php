<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Console;

use Contenir\Db\Model\Tools\Console\CommandInput;
use Contenir\Db\Model\Tools\Console\Connection;
use Contenir\Db\Model\Tools\Exception\ToolException;
use PhpDb\Adapter\Adapter;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

#[CoversClass(Connection::class)]
#[CoversClass(CommandInput::class)]
#[CoversClass(ToolException::class)]
#[Group('unit')]
final class ConnectionTest extends TestCase
{
    private static function input(?string $dsn): CommandInput
    {
        $parameters = null === $dsn ? [] : ['--dsn' => $dsn];

        return new CommandInput(new ArrayInput($parameters, new InputDefinition([
            new InputOption('dsn', null, InputOption::VALUE_REQUIRED),
        ])));
    }

    #[Test]
    public function dsnTakesPrecedenceOverTheInjectedAdapter(): void
    {
        $adapter = static::createStub(Adapter::class);

        static::assertNotSame($adapter, Connection::resolve(self::input('sqlite::memory:'), $adapter));
    }

    #[Test]
    public function failsWithoutAnyConnection(): void
    {
        $this->expectExceptionObject(ToolException::noConnection());

        Connection::resolve(self::input(null), null);
    }

    #[Test]
    public function usesTheInjectedAdapterWithoutADsn(): void
    {
        $adapter = static::createStub(Adapter::class);

        static::assertSame($adapter, Connection::resolve(self::input(null), $adapter));
    }
}
