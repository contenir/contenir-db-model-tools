<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Container;

use Contenir\Db\Model\Metadata\MetadataFactoryInterface;
use Contenir\Db\Model\Tools\Command\GenerateEntityCommand;
use Contenir\Db\Model\Tools\Command\UpgradeEntityCommand;
use Contenir\Db\Model\Tools\Command\ValidateMappingCommand;
use Contenir\Db\Model\Tools\ConfigProvider;
use Contenir\Db\Model\Tools\Container\CommandFactory;
use Contenir\Db\Model\Tools\Module;
use ContenirTest\Db\Model\Tools\TestAsset\Container\ArrayContainer;
use LogicException;
use PhpDb\Adapter\Adapter;
use PhpDb\Adapter\AdapterInterface;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

use function array_keys;
use function str_contains;

#[CoversClass(CommandFactory::class)]
#[CoversClass(ConfigProvider::class)]
#[CoversClass(Module::class)]
#[Group('unit')]
final class CommandFactoryTest extends TestCase
{
    /**
     * @return array<string, array{class-string}>
     */
    public static function commandProvider(): array
    {
        return [
            'generate' => [GenerateEntityCommand::class],
            'upgrade'  => [UpgradeEntityCommand::class],
            'validate' => [ValidateMappingCommand::class],
        ];
    }

    #[DataProvider('commandProvider')]
    #[Test]
    public function buildsEachRegisteredCommand(string $class): void
    {
        static::assertInstanceOf($class, (new CommandFactory())(new ArrayContainer([]), $class));
    }

    #[Test]
    public function ignoresAdaptersThatCannotReadSchemas(): void
    {
        $container = new ArrayContainer([AdapterInterface::class => static::createStub(AdapterInterface::class)]);
        $tester    = new CommandTester((new CommandFactory())($container, UpgradeEntityCommand::class));
        $tester->execute(['paths' => ['x']]);

        static::assertTrue(str_contains($tester->getDisplay(), 'No database connection'));
    }

    #[Test]
    public function registersCommandsForLaminasCliAndMvc(): void
    {
        $provider = (new ConfigProvider())();
        $module   = (new Module())->getConfig();

        static::assertSame(
            [
                ['db-model:entity:generate', 'db-model:entity:upgrade', 'db-model:mapping:validate'],
                CommandFactory::class,
                $provider['laminas-cli'],
                $provider['dependencies'],
            ],
            [
                array_keys($provider['laminas-cli']['commands']),
                $provider['dependencies']['factories'][UpgradeEntityCommand::class],
                $module['laminas-cli'],
                $module['service_manager'],
            ],
        );
    }

    #[Test]
    public function usesTheConfiguredAdapterService(): void
    {
        $adapter = static::createStub(Adapter::class);
        $adapter->method('getPlatform')->willThrowException(new LogicException('adapter used'));
        $command = (new CommandFactory())(
            new ArrayContainer([
                'config'                        => ['contenir_db_model' => ['adapter' => 'db.main']],
                'db.main'                       => $adapter,
                MetadataFactoryInterface::class => static::createStub(MetadataFactoryInterface::class),
            ]),
            ValidateMappingCommand::class,
        );

        $this->expectExceptionObject(new LogicException('adapter used'));

        (new CommandTester($command))->execute(['classes' => ['X']]);
    }

    #[Test]
    public function withoutAnAdapterTheCommandsNeedADsn(): void
    {
        $command = (new CommandFactory())(new ArrayContainer(['config' => 'invalid']), GenerateEntityCommand::class);
        $tester  = new CommandTester($command);
        $tester->execute(['--all' => true]);

        static::assertStringContainsString('No database connection', $tester->getDisplay());
    }
}
