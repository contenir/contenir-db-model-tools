<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Integration\Command;

use Contenir\Db\Model\Metadata\AttributeMetadataFactory;
use Contenir\Db\Model\Tools\Command\ValidateMappingCommand;
use Contenir\Db\Model\Tools\Console\Application;
use ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken\AuditLog;
use ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken\MissingTable;
use ContenirTest\Db\Model\Tools\TestAsset\Entity\User;
use ContenirTest\Db\Model\Tools\Trait\TestDatabaseTrait;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function dirname;
use function explode;
use function str_contains;
use function trim;

#[CoversClass(ValidateMappingCommand::class)]
#[CoversClass(Application::class)]
#[Group('integration')]
final class ValidateMappingCommandTest extends TestCase
{
    use TestDatabaseTrait;

    #[Test]
    public function applicationRegistersTheCommand(): void
    {
        static::assertInstanceOf(ValidateMappingCommand::class, (new Application())->find('mapping:validate'));
    }

    #[Test]
    public function defaultsToAttributeMetadata(): void
    {
        $tester = new CommandTester(new ValidateMappingCommand($this->adapter));

        static::assertSame(Command::SUCCESS, $tester->execute(['classes' => [User::class]]));
    }

    #[Test]
    public function failsOnErrors(): void
    {
        $tester = $this->tester();
        $tester->execute(['classes' => [MissingTable::class]]);

        static::assertSame(
            [Command::FAILURE, true, true],
            [
                $tester->getStatusCode(),
                str_contains($tester->getDisplay(), 'FAIL ' . MissingTable::class),
                str_contains($tester->getDisplay(), 'error Table "nope" does not exist'),
            ],
        );
    }

    #[Test]
    public function passesMatchingEntities(): void
    {
        $tester = $this->tester();
        $tester->execute(['classes' => [User::class]]);

        static::assertSame(
            [Command::SUCCESS, 'OK   ' . User::class, '1 entities checked: 0 errors, 0 warnings'],
            [$tester->getStatusCode(), ...explode("\n", trim($tester->getDisplay()))],
        );
    }

    #[Test]
    public function rejectsEmptySelection(): void
    {
        static::assertSame(Command::INVALID, $this->tester()->execute([]));
    }

    #[Test]
    public function reportsUnreadablePaths(): void
    {
        $tester = $this->tester();

        static::assertSame(Command::FAILURE, $tester->execute(['--path' => ['/nonexistent/entities']]));
    }

    #[Test]
    public function scansPathsForEntities(): void
    {
        $tester = $this->tester();
        $tester->execute(['--path' => [dirname(__DIR__, levels: 2) . '/TestAsset/Entity'], 'classes' => [User::class]]);

        static::assertStringContainsString('10 entities checked: ', $tester->getDisplay());
    }

    #[Test]
    public function warningsPassUnlessStrict(): void
    {
        $tester = $this->tester();
        $loose  = $tester->execute(['classes' => [AuditLog::class]]);
        $strict = $tester->execute(['classes' => [AuditLog::class], '--strict' => true]);

        static::assertSame(
            [Command::SUCCESS, Command::FAILURE, true],
            [$loose, $strict, str_contains($tester->getDisplay(), 'WARN ' . AuditLog::class)],
        );
    }

    protected function setUp(): void
    {
        $this->setUpTestDatabase();
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new ValidateMappingCommand($this->adapter, new AttributeMetadataFactory()));
    }
}
