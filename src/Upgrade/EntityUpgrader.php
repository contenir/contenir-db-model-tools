<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Contenir\Db\Model\Tools\Exception\NotLegacyEntityException;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Exception\UpgradeException;
use Contenir\Db\Model\Tools\Schema\SchemaReader;

use function file_get_contents;
use function is_file;

/**
 * Upgrades a 1.x entity file to 2.x attributes and typed properties,
 * keeping its class name, namespace and every other member. Column types
 * come from the live table.
 *
 * @api
 */
final readonly class EntityUpgrader
{
    private LegacyEntityParser $parser;

    public function __construct(
        private SchemaReader $reader,
        private UpgradeOptions $options = new UpgradeOptions(),
    ) {
        $this->parser = new LegacyEntityParser(new RelationConfigReader(new TargetResolver($options->targets)));
    }

    /**
     * @throws NotLegacyEntityException When the file holds no 1.x entity.
     * @throws ToolException When the file or table cannot be read.
     * @throws UpgradeException When the entity cannot be converted.
     */
    public function upgrade(string $file): UpgradeResult
    {
        if (! is_file($file)) {
            throw ToolException::cannotRead($file, 'no such file');
        }

        $notes = [];
        [$source, $entity] = $this->parser->parse($file, (string) file_get_contents($file), $notes);
        $table =
            $this->options->table
                ?? TableLocator::find($file, $entity->className)
                ?? throw UpgradeException::tableUnknown($entity->className);

        $mapped = (new MappedClassBuilder($this->options->columnNames))->build(
            $entity,
            $this->reader->read($table, $this->options->schema),
            $notes,
        );
        $notes = [...$notes, ...LegacyApiScanner::scan($source->class)];

        $repositories = [];
        foreach ($entity->relations as $relation) {
            $repositories[] = $relation->target->repository;
        }

        return new UpgradeResult(
            $entity->className,
            AstGrafter::graft($source, $mapped, $repositories, $notes),
            $notes,
        );
    }
}
