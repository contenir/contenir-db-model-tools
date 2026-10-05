<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Generator;

use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Tools\Schema\TableSchema;
use Nette\PhpGenerator\PhpFile;
use Nette\PhpGenerator\PsrPrinter;

/**
 * Renders a {@see TableSchema} as a contenir-db-model entity class.
 *
 * @api
 */
final readonly class EntityGenerator
{
    public function __construct(
        private EntityOptions $options = new EntityOptions(),
    ) {}

    public function generate(TableSchema $table): GeneratedClass
    {
        $file = new PhpFile();
        $file->setStrictTypes();
        $namespace = $file->addNamespace($this->options->namespace);
        $className = $this->options->classFor($table->name);
        $class     = $namespace->addClass($className)->setFinal();

        $namespace->addUse(Table::class);
        $class->addAttribute(
            Table::class,
            null === $table->schema ? [$table->name] : [$table->name, 'schema' => $table->schema],
        );

        $columns    = new ColumnRenderer($this->options->versionColumn);
        $properties = [];
        foreach ($table->columns as $column) {
            $properties[] = $columns->render($namespace, $class, $table, $column);
        }

        if ($this->options->relations) {
            (new RelationRenderer($this->options))->render($namespace, $class, $table->foreignKeys, $properties);
        }

        return new GeneratedClass($className, (new PsrPrinter())->printFile($file));
    }
}
