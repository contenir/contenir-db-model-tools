<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Command;

use Contenir\Db\Model\Metadata\AttributeMetadataFactory;
use Contenir\Db\Model\Metadata\MetadataFactoryInterface;
use Contenir\Db\Model\Tools\Console\CommandInput;
use Contenir\Db\Model\Tools\Console\Connection;
use Contenir\Db\Model\Tools\Discovery\EntityFinder;
use Contenir\Db\Model\Tools\Exception\ExceptionInterface;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Schema\SchemaReader;
use Contenir\Db\Model\Tools\Validator\Issue;
use Contenir\Db\Model\Tools\Validator\SchemaValidator;
use Contenir\Db\Model\Tools\Validator\Severity;
use Contenir\Db\Model\Tools\Validator\ValidationReport;
use Override;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

use function array_map;
use function array_push;
use function array_unique;
use function array_values;
use function in_array;
use function sprintf;

/**
 * Checks entity mappings against the live database schema.
 *
 * @api
 */
#[AsCommand(name: 'mapping:validate', description: 'Check entity mappings against the database schema')]
final class ValidateMappingCommand extends Command
{
    public function __construct(
        private readonly (AdapterInterface&SchemaAwareInterface)|null $adapter = null,
        private readonly ?MetadataFactoryInterface $metadata = null,
    ) {
        parent::__construct();
    }

    /**
     * @return list<string>
     *
     * @throws ToolException
     */
    private static function classes(CommandInput $options): array
    {
        $classes = $options->arguments('classes');
        $finder  = new EntityFinder();
        foreach ($options->options('path') as $path) {
            array_push($classes, ...$finder->find($path));
        }

        return array_values(array_unique($classes));
    }

    private static function print(ValidationReport $report, OutputInterface $output): void
    {
        foreach ($report->entities() as $class => $issues) {
            $output->writeln(self::status($issues) . " {$class}");
            foreach ($issues as $issue) {
                $tag = Severity::Error === $issue->severity ? 'error' : 'comment';
                $output->writeln("       <{$tag}>{$issue->severity->value}</{$tag}> {$issue->message}");
            }
        }

        $output->writeln(sprintf(
            '%d entities checked: %d errors, %d warnings',
            $report->size(),
            $report->count(Severity::Error),
            $report->count(Severity::Warning),
        ));
    }

    /**
     * @param list<Issue> $issues
     */
    private static function status(array $issues): string
    {
        $severities = array_map(static fn(Issue $issue): Severity => $issue->severity, $issues);

        return match (true) {
            in_array(Severity::Error, $severities, strict: true) => '<error>FAIL</error>',
            [] !== $severities => '<comment>WARN</comment>',
            default => '<info>OK</info>  ',
        };
    }

    #[Override]
    protected function configure(): void
    {
        $this->addArgument('classes', InputArgument::IS_ARRAY, 'Entity classes to validate')
            ->addOption(
                'path',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Directory to scan for #[Table] classes (repeatable)',
            )
            ->addOption(
                'dsn',
                null,
                InputOption::VALUE_REQUIRED,
                'sqlite:///path.db, mysql://user:pass@host/db or pgsql://...',
            )
            ->addOption('strict', null, InputOption::VALUE_NONE, 'Fail on warnings as well as errors');
    }

    #[Override]
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $options = new CommandInput($input);

        try {
            $classes = self::classes($options);
            if ([] === $classes) {
                $output->writeln('<error>Name entity classes, or pass --path to scan a directory</error>');

                return self::INVALID;
            }

            $validator = new SchemaValidator(
                new SchemaReader(Connection::resolve($options, $this->adapter)),
                $this->metadata ?? new AttributeMetadataFactory(),
            );
            $report = new ValidationReport();
            foreach ($classes as $class) {
                $report->add($class, $validator->validate($class));
            }
        } catch (ExceptionInterface $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return self::FAILURE;
        }

        self::print($report, $output);

        return $report->passes($options->flag('strict')) ? self::SUCCESS : self::FAILURE;
    }
}
