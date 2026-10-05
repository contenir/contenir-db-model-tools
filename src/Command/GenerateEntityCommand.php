<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Command;

use Contenir\Db\Model\Tools\Console\CommandInput;
use Contenir\Db\Model\Tools\Console\Connection;
use Contenir\Db\Model\Tools\Exception\ExceptionInterface;
use Contenir\Db\Model\Tools\Generator\EntityGenerator;
use Contenir\Db\Model\Tools\Generator\EntityOptions;
use Contenir\Db\Model\Tools\Schema\SchemaReader;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function count;

/**
 * Generates entity classes from live tables.
 *
 * @api
 */
#[AsCommand(name: 'entity:generate', description: 'Generate contenir-db-model entity classes from database tables')]
final class GenerateEntityCommand extends Command
{
    public function __construct(
        private readonly (AdapterInterface&SchemaAwareInterface)|null $adapter = null,
    ) {
        parent::__construct();
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('tables', InputArgument::IS_ARRAY, 'Tables to generate entities for')
            ->addOption('all', null, InputOption::VALUE_NONE, 'Generate every table')
            ->addOption(
                'dsn',
                null,
                InputOption::VALUE_REQUIRED,
                'sqlite:///path.db, mysql://user:pass@host/db or pgsql://...',
            )
            ->addOption('schema', null, InputOption::VALUE_REQUIRED, 'Database schema to read')
            ->addOption(
                'namespace',
                null,
                InputOption::VALUE_REQUIRED,
                'Namespace for the classes',
                EntityOptions::DEFAULT_NAMESPACE,
            )
            ->addOption('output', null, InputOption::VALUE_REQUIRED, 'Directory to write class files to', 'src/Entity')
            ->addOption('class', null, InputOption::VALUE_REQUIRED, 'Class name, when generating a single table')
            ->addOption('version-column', null, InputOption::VALUE_REQUIRED, 'Column to map as #[Version]')
            ->addOption(
                'no-relations',
                null,
                InputOption::VALUE_NONE,
                'Do not turn foreign keys into #[BelongsTo] relations',
            )
            ->addOption('force', null, InputOption::VALUE_NONE, 'Overwrite existing files')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Print the classes instead of writing them');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = new CommandInput($input);

        try {
            $reader = new SchemaReader(Connection::resolve($options, $this->adapter));
            $schema = $options->option('schema');
            $tables = $options->flag('all') ? $reader->tableNames($schema) : $options->arguments('tables');
            $class  = $options->option('class');
            if ([] === $tables || (null !== $class && 1 !== count($tables))) {
                $output->writeln('<error>Name one table (with --class) or several tables, or pass --all</error>');

                return self::INVALID;
            }

            $generator = new EntityGenerator(new EntityOptions(
                $options->option('namespace') ?? EntityOptions::DEFAULT_NAMESPACE,
                null === $class ? [] : [$tables[0] => $class],
                ! $options->flag('no-relations'),
                $options->option('version-column'),
            ));
            $writer = new EntityWriter($options->option('output') ?? 'src/Entity', $options->flag('force'));

            foreach ($tables as $table) {
                $generated = $generator->generate($reader->read($table, $schema));
                $output->writeln($options->flag('dry-run') ? $generated->source : "Wrote {$writer->write($generated)}");
            }
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
