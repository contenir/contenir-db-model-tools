<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Integration\Command;

use Contenir\Db\Model\Tools\Command\GenerateEntityCommand;
use Contenir\Db\Model\Tools\Console\Application;
use ContenirTest\Db\Model\Tools\Trait\TestDatabaseTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function file_get_contents;
use function str_contains;

#[CoversClass(GenerateEntityCommand::class)]
#[CoversClass(Application::class)]
#[Group('integration')]
final class GenerateEntityCommandTest extends TestCase
{
    use TestDatabaseTrait;

    /**
     * @mago-expect lint:no-literal-namespace-string The generated namespace is data, not a class reference.
     */
    private const string NAMESPACE = 'Shop\\Entity';

    private vfsStreamDirectory $root;

    #[Test]
    public function applicationRegistersTheCommand(): void
    {
        static::assertInstanceOf(GenerateEntityCommand::class, (new Application())->find('entity:generate'));
    }

    #[Test]
    public function dryRunPrintsWithoutWriting(): void
    {
        $tester = $this->tester();
        $tester->execute([
            'tables'         => ['order_items'],
            '--dry-run'      => true,
            '--no-relations' => true,
            '--output'       => $this->root->url(),
        ]);

        static::assertSame([false, true, false], [
            $this->root->hasChildren(),
            str_contains($tester->getDisplay(), 'final class OrderItem'),
            str_contains($tester->getDisplay(), 'BelongsTo'),
        ]);
    }

    #[Test]
    public function dsnReplacesTheInjectedConnection(): void
    {
        $tester = $this->tester();
        $tester->execute(['tables' => ['users'], '--dsn' => 'sqlite::memory:', '--output' => $this->root->url()]);

        static::assertSame(
            [Command::FAILURE, false],
            [$tester->getStatusCode(), $this->root->hasChildren()],
        );
    }

    #[Test]
    public function failsWithoutAConnection(): void
    {
        $tester = new CommandTester(new GenerateEntityCommand());

        static::assertSame(Command::FAILURE, $tester->execute(['--all' => true]));
    }

    #[Test]
    public function namesASingleClassAndMapsItsVersionColumn(): void
    {
        $tester = $this->tester();
        $tester->execute([
            'tables'           => ['users'],
            '--class'          => 'Customer',
            '--version-column' => 'version',
            '--output'         => $this->root->url(),
        ]);

        static::assertStringContainsString(
            '#[Version]',
            (string) file_get_contents("{$this->root->url()}/Customer.php"),
        );
    }

    #[Test]
    public function rejectsAClassNameForSeveralTables(): void
    {
        $tester = $this->tester();

        static::assertSame(Command::INVALID, $tester->execute(['tables' => ['users', 'user_tag'], '--class' => 'X']));
    }

    #[Test]
    public function rejectsAMissingTableList(): void
    {
        static::assertSame(Command::INVALID, $this->tester()->execute([]));
    }

    #[Test]
    public function reportsToolFailures(): void
    {
        $tester = $this->tester();
        $tester->execute(['tables' => ['nope'], '--output' => $this->root->url()]);

        static::assertSame(
            [Command::FAILURE, true],
            [$tester->getStatusCode(), str_contains($tester->getDisplay(), 'nope')],
        );
    }

    #[Test]
    public function writesAnEntityPerTable(): void
    {
        $tester = $this->tester();
        $tester->execute([
            'tables'      => ['users', 'order_items', 'user_tag'],
            '--output'    => $this->root->url(),
            '--namespace' => self::NAMESPACE,
        ]);

        static::assertSame(
            [Command::SUCCESS, true, true, true, true],
            [
                $tester->getStatusCode(),
                str_contains($tester->getDisplay(), 'Wrote vfs://root/User.php'),
                $this->root->hasChild('OrderItem.php'),
                $this->root->hasChild('UserTag.php'),
                str_contains(
                    (string) file_get_contents("{$this->root->url()}/User.php"),
                    'namespace ' . self::NAMESPACE . ';',
                ),
            ],
        );
    }

    protected function setUp(): void
    {
        $this->setUpTestDatabase();
        $this->root = vfsStream::setup();
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new GenerateEntityCommand($this->adapter));
    }
}
