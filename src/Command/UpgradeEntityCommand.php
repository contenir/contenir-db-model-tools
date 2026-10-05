<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Command;

use Contenir\Db\Model\Tools\Console\CommandInput;
use Contenir\Db\Model\Tools\Console\Connection;
use Contenir\Db\Model\Tools\Console\UpgradeInput;
use Contenir\Db\Model\Tools\Exception\ExceptionInterface;
use Contenir\Db\Model\Tools\Exception\NotLegacyEntityException;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Generator\GeneratedClass;
use Contenir\Db\Model\Tools\Schema\SchemaReader;
use Contenir\Db\Model\Tools\Upgrade\EntityUpgrader;
use Contenir\Db\Model\Tools\Upgrade\UpgradeResult;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function basename;
use function count;
use function dirname;

/**
 * Upgrades contenir-db-model 1.x entities to 2.x attributes in place.
 *
 * @api
 */
#[AsCommand(name: 'entity:upgrade', description: 'Upgrade contenir-db-model 1.x entities to 2.x attributes')]
final class UpgradeEntityCommand extends Command
{
    public function __construct(
        private readonly (AdapterInterface&SchemaAwareInterface)|null $adapter = null,
    ) {
        parent::__construct();
    }

    /**
     * @return bool false when the file could not be upgraded
     */
    private static function upgrade(
        EntityUpgrader $upgrader,
        string $file,
        CommandInput $options,
        OutputInterface $output,
    ): bool {
        try {
            $result = $upgrader->upgrade($file);
            $output->writeln($options->flag('dry-run') ? $result->source : self::write($file, $result, $options));
        } catch (NotLegacyEntityException) {
            return true;
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return false;
        }

        foreach ($result->notes as $note) {
            $output->writeln("  <comment>check</comment> {$note}");
        }

        return true;
    }

    /**
     * @return string the line to report
     *
     * @throws ToolException
     */
    private static function write(string $file, UpgradeResult $result, CommandInput $options): string
    {
        $directory = $options->option('output');
        $writer    = new EntityWriter($directory ?? dirname($file), null === $directory || $options->flag('force'));
        $path      = $writer->write(new GeneratedClass(basename($file, suffix: '.php'), $result->source));

        return "Upgraded {$result->className} → {$path}";
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('paths', InputArgument::IS_ARRAY | InputArgument::REQUIRED, 'Entity files or directories')
            ->addOption(
                'dsn',
                null,
                InputOption::VALUE_REQUIRED,
                'sqlite:///path.db, mysql://user:pass@host/db or pgsql://...',
            )
            ->addOption('schema', null, InputOption::VALUE_REQUIRED, 'Database schema to read')
            ->addOption('table', null, InputOption::VALUE_REQUIRED, 'Table, for one file without a 1.x repository')
            ->addOption('camel-case', null, InputOption::VALUE_NONE, 'Rename properties to camelCase ($createdAt)')
            ->addOption(
                'map',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Repository=Entity class pair for relation targets (repeatable; default: 1.x naming convention)',
            )
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Write to this directory instead of in place')
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite files in --output')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the upgraded classes instead of writing them');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = new CommandInput($input);

        try {
            $files = UpgradeInput::files($options);
            if (null !== $options->option('table') && 1 !== count($files)) {
                $output->writeln('<error>--table applies to a single file</error>');

                return self::INVALID;
            }

            $upgrader = new EntityUpgrader(
                new SchemaReader(Connection::resolve($options, $this->adapter)),
                UpgradeInput::options($options),
            );
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }

        $failed = 0;
        foreach ($files as $file) {
            $failed += (int) ! self::upgrade($upgrader, $file, $options, $output);
        }

        return 0 === $failed ? self::SUCCESS : self::FAILURE;
    }
}
