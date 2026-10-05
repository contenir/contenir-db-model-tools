<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Integration\Command;

use Contenir\Db\Model\Tools\Command\UpgradeEntityCommand;
use Contenir\Db\Model\Tools\Console\Application;
use Contenir\Db\Model\Tools\Console\UpgradeInput;
use Contenir\Db\Model\Tools\Exception\UpgradeException;
use ContenirTest\Db\Model\Tools\Trait\TestDatabaseTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

use function dirname;
use function file_get_contents;
use function str_contains;

#[CoversClass(UpgradeEntityCommand::class)]
#[CoversClass(UpgradeInput::class)]
#[CoversClass(UpgradeException::class)]
#[CoversClass(Application::class)]
#[Group('integration')]
final class UpgradeEntityCommandTest extends TestCase
{
    use TestDatabaseTrait;

    private vfsStreamDirectory $root;

    #[Test]
    public function applicationRegistersTheCommand(): void
    {
        static::assertInstanceOf(UpgradeEntityCommand::class, (new Application())->find('entity:upgrade'));
    }

    #[Test]
    public function camelCasesOnRequest(): void
    {
        $tester = $this->tester();
        $tester->execute([
            'paths'        => ["{$this->entities()}/UserEntity.php"],
            '--dry-run'    => true,
            '--camel-case' => true,
        ]);

        static::assertStringContainsString('public DateTimeImmutable $createdAt;', $tester->getDisplay());
    }

    #[Test]
    public function dryRunPrintsWithoutWriting(): void
    {
        $tester = $this->tester();
        $tester->execute(['paths' => ["{$this->entities()}/TagEntity.php"], '--dry-run' => true]);

        static::assertSame(
            [true, true],
            [
                str_contains($tester->getDisplay(), "#[Table('tags')]"),
                str_contains((string) file_get_contents("{$this->entities()}/TagEntity.php"), 'extends AbstractEntity'),
            ],
        );
    }

    #[Test]
    public function failsWithoutAConnection(): void
    {
        $tester = new CommandTester(new UpgradeEntityCommand());

        static::assertSame(Command::FAILURE, $tester->execute(['paths' => [$this->entities()]]));
    }

    #[Test]
    public function mapsRelationTargetsExplicitly(): void
    {
        $tester = $this->tester();
        $tester->execute([
            'paths'     => ["{$this->entities()}/OrderItemEntity.php"],
            '--dry-run' => true,
            '--map'     => ['Legacy\\Repository\\UserRepository=App\\Model\\User'],
        ]);

        static::assertStringContainsString('public ?\\App\\Model\\User $user;', $tester->getDisplay());
    }

    #[Test]
    public function rejectsATableForSeveralFiles(): void
    {
        static::assertSame(Command::INVALID, $this->tester()->execute([
            'paths'   => [$this->entities()],
            '--table' => 'tags',
        ]));
    }

    #[Test]
    public function rejectsMalformedMaps(): void
    {
        $tester = $this->tester();
        $tester->execute(['paths' => [$this->entities()], '--map' => ['=App\\User']]);

        static::assertSame(
            [Command::FAILURE, true],
            [$tester->getStatusCode(), str_contains($tester->getDisplay(), 'must be RepositoryClass=EntityClass')],
        );
    }

    #[Test]
    public function skipsNonEntitiesAndReportsFailures(): void
    {
        vfsStream::create([
            'src' => ['Entity' => [
                'Plain.php'  => "<?php\nclass Plain {}",
                'Broken.php' => "<?php\nclass Broken { protected \$columns = [X]; }",
            ]],
        ], $this->root);
        $tester = $this->tester();
        $tester->execute(['paths' => [$this->entities()], '--dry-run' => true]);

        static::assertSame(
            [Command::FAILURE, false, true],
            [
                $tester->getStatusCode(),
                str_contains($tester->getDisplay(), 'Plain'),
                str_contains($tester->getDisplay(), 'Broken.php: $columns must be a constant expression'),
            ],
        );
    }

    #[Test]
    public function takesTheTableForASingleFile(): void
    {
        vfsStream::newFile('Loose.php')->withContent(
            "<?php\nclass Loose { protected \$columns = ['id']; }",
        )->at($this->root);
        $tester = $this->tester();

        static::assertSame(Command::SUCCESS, $tester->execute([
            'paths'     => ["{$this->root->url()}/Loose.php"],
            '--table'   => 'tags',
            '--dry-run' => true,
        ]));
    }

    #[Test]
    public function upgradesADirectoryInPlace(): void
    {
        $tester = $this->tester();
        $tester->execute(['paths' => [$this->entities()]]);

        static::assertSame(
            [Command::SUCCESS, true, true, true],
            [
                $tester->getStatusCode(),
                str_contains(
                    $tester->getDisplay(),
                    'Upgraded Legacy\\Entity\\UserEntity → vfs://root/src/Entity/UserEntity.php',
                ),
                str_contains($tester->getDisplay(), '  check method jsonSerialize() uses the 1.x entity API'),
                str_contains((string) file_get_contents("{$this->entities()}/TagEntity.php"), "#[Table('tags')]"),
            ],
        );
    }

    #[Test]
    public function writesToAnOutputDirectoryAndProtectsExistingFiles(): void
    {
        $tester     = $this->tester();
        $parameters = ['paths' => ["{$this->entities()}/TagEntity.php"], '--output' => "{$this->root->url()}/out"];
        $first      = $tester->execute($parameters);
        $second     = $tester->execute($parameters);
        $forced     = $tester->execute([...$parameters, '--force' => true]);

        static::assertSame([Command::SUCCESS, Command::FAILURE, Command::SUCCESS], [$first, $second, $forced]);
    }

    protected function setUp(): void
    {
        $this->setUpTestDatabase();
        $this->root = vfsStream::setup();
        vfsStream::copyFromFileSystem(
            dirname(__DIR__, levels: 2) . '/Fixture/Legacy/src',
            vfsStream::newDirectory('src')->at($this->root),
        );
    }

    private function entities(): string
    {
        return "{$this->root->url()}/src/Entity";
    }

    private function tester(): CommandTester
    {
        return new CommandTester(new UpgradeEntityCommand($this->adapter));
    }
}
