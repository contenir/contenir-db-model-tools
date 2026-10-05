<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Tools\Exception\UpgradeException;
use Contenir\Db\Model\Tools\Generator\ColumnRenderer;
use Contenir\Db\Model\Tools\Schema\TableSchema;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

/**
 * Renders the 2.x mapping of a 1.x entity (#[Table], mapped column and
 * relation properties, their imports) as a class, for grafting into the
 * original file. Column types come from the live table.
 *
 * @internal
 */
final readonly class MappedClassBuilder
{
    /**
     * @param bool $columnNames keep column names as property names (1.x accessed $entity->created_at)
     */
    public function __construct(
        private bool $columnNames = true,
    ) {}

    /**
     * The table narrowed to the entity's 1.x columns (all columns when it
     * listed none), keyed on its 1.x primary key (the table's when none).
     *
     * @throws UpgradeException
     */
    private static function mappedTable(LegacyEntity $entity, TableSchema $table): TableSchema
    {
        $columns = [];
        foreach ($entity->columns as $name) {
            $columns[] = $table->column($name) ?? throw UpgradeException::columnNotFound(
                $entity->className,
                $name,
                $table->name,
            );
        }

        return new TableSchema(
            $table->name,
            $table->schema,
            [] === $entity->columns ? $table->columns : $columns,
            [] === $entity->primaryKeys ? $table->primaryKey : $entity->primaryKeys,
        );
    }

    /**
     * @param list<string> $notes receives notes on settings that cannot be carried over
     *
     * @throws UpgradeException When the entity lists a column the table lacks.
     */
    public function build(LegacyEntity $entity, TableSchema $table, array &$notes): string
    {
        $mapped = self::mappedTable($entity, $table);
        $file   = new PhpFile();
        $space  = $file->addNamespace($entity->namespace());
        $class  = $space->addClass($entity->shortName());
        $space->addUse(Table::class);
        $class->addAttribute(
            Table::class,
            null === $table->schema ? [$table->name] : [$table->name, 'schema' => $table->schema],
        );

        $columns = new ColumnRenderer($entity->versionColumn, $this->columnNames);
        foreach ($mapped->columns as $column) {
            $columns->render($space, $class, $mapped, $column);
        }

        LegacyRelationRenderer::render($space, $class, $entity, $notes);

        return (new PsrPrinter())->printFile($file);
    }
}
