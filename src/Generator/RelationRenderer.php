<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Generator;

use Contenir\Db\Model\Mapping\BelongsTo;
use Contenir\Db\Model\Relation\LazyRelationsTrait;
use Contenir\Db\Model\Tools\Schema\ForeignKeySchema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;

use function array_key_exists;
use function count;

/**
 * Turns foreign keys into #[BelongsTo] relation properties, using
 * LazyRelationsTrait so they load on first read.
 *
 * @internal
 */
final readonly class RelationRenderer
{
    public function __construct(
        private EntityOptions $options,
    ) {}

    /**
     * @param list<string> $columns
     *
     * @return string|list<string>
     */
    private static function keys(array $columns): string|array
    {
        return 1 === count($columns) ? $columns[0] : $columns;
    }

    private static function nullable(ClassType $class, ForeignKeySchema $foreignKey): bool
    {
        foreach ($foreignKey->columns as $column) {
            $property = Inflector::property($column);
            if ($class->hasProperty($property) && $class->getProperty($property)->isNullable()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param list<ForeignKeySchema> $foreignKeys
     * @param list<string>           $taken       property names already used
     */
    public function render(PhpNamespace $namespace, ClassType $class, array $foreignKeys, array $taken): void
    {
        if ([] === $foreignKeys) {
            return;
        }

        $names = [];
        foreach ($taken as $name) {
            $names[$name] = true;
        }

        $namespace->addUse(BelongsTo::class);
        $namespace->addUse(LazyRelationsTrait::class);
        $class->addTrait(LazyRelationsTrait::class);

        foreach ($foreignKeys as $foreignKey) {
            $name         = Inflector::relation($foreignKey->columns, $foreignKey->referencedTable);
            $name         = array_key_exists($name, $names) ? "{$name}Entity" : $name;
            $names[$name] = true;
            $target       = "{$this->options->namespace}\\{$this->options->classFor($foreignKey->referencedTable)}";

            $class->addProperty($name)
                ->setType($target)
                ->setNullable(self::nullable($class, $foreignKey))
                ->addAttribute(
                    BelongsTo::class,
                    [
                        new Literal("{$namespace->simplifyName($target)}::class"),
                        'foreignKey' => self::keys($foreignKey->columns),
                        'ownerKey'   => self::keys($foreignKey->referencedColumns),
                    ],
                );
        }
    }
}
