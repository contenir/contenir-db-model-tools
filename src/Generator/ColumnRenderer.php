<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Generator;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Version;
use Contenir\Db\Model\Tools\Schema\ColumnSchema;
use Contenir\Db\Model\Tools\Schema\TableSchema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use Nette\PhpGenerator\Property;

use function class_exists;

/**
 * Adds one column-mapped property, with #[Id], #[Version] and #[Column]
 * attributes as needed, to a generated entity class.
 *
 * @internal
 */
final readonly class ColumnRenderer
{
    public function __construct(
        private ?string $versionColumn,
    ) {}

    /**
     * #[Column] arguments: the column name when it differs from the
     * property, and the converter when the PHP type alone does not pick it.
     *
     * @return array<int|string, string>
     */
    private static function arguments(string $name, ColumnSchema $column, PropertyType $type): array
    {
        $arguments = $name === $column->name ? [] : [$column->name];
        if (null !== $type->converter) {
            $arguments['type'] = $type->converter;
        }

        return $arguments;
    }

    /**
     * @return string the property name
     */
    public function render(PhpNamespace $namespace, ClassType $class, TableSchema $table, ColumnSchema $column): string
    {
        $name     = Inflector::property($column->name);
        $type     = TypeMapper::map($column);
        $nullable = $column->nullable || $column->generated;
        $property = $class->addProperty($name)->setType($type->phpType)->setNullable($nullable);
        if (class_exists($type->phpType)) {
            $namespace->addUse($type->phpType);
        }

        $default = $nullable ? null : TypeMapper::defaultValue($column, $type);
        if ($nullable || null !== $default) {
            $property->setValue($default);
        }

        $implied   = $this->role($namespace, $property, $table, $column);
        $arguments = self::arguments($name, $column, $type);
        if (! $implied || [] !== $arguments) {
            $namespace->addUse(Column::class);
            $property->addAttribute(Column::class, $arguments);
        }

        return $name;
    }

    /**
     * @return bool whether an #[Id] or #[Version] attribute was added (both imply a column)
     */
    private function role(PhpNamespace $namespace, Property $property, TableSchema $table, ColumnSchema $column): bool
    {
        if ($table->isPrimaryKey($column->name)) {
            $namespace->addUse(Id::class);
            $property->addAttribute(Id::class, $column->generated ? ['generated' => true] : []);

            return true;
        }

        if ($column->name !== $this->versionColumn) {
            return false;
        }

        $namespace->addUse(Version::class);
        $property->addAttribute(Version::class);

        return true;
    }
}
