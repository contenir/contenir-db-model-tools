<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Schema;

use Contenir\Db\Model\Tools\Exception\ToolException;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use PhpDb\Metadata\MetadataInterface;
use PhpDb\Metadata\Object\ColumnObject;

use function array_values;
use function in_array;
use function preg_replace;
use function strtolower;
use function trim;

/**
 * Reads table structure from a live database through phpdb's metadata,
 * normalising type names and adding generated-key and boolean detection.
 *
 * @api
 */
final readonly class SchemaReader
{
    private MetadataInterface $metadata;

    private ColumnExtras $extras;

    private Platform $platform;

    /**
     * @throws ToolException
     */
    public function __construct(AdapterInterface&SchemaAwareInterface $adapter)
    {
        $this->platform = Platform::of($adapter);
        $this->metadata = $this->platform->metadata($adapter);
        $this->extras   = new ColumnExtras($adapter, $this->platform);
    }

    /**
     * @throws ToolException When the table does not exist.
     */
    public function read(string $table, ?string $schema = null): TableSchema
    {
        if (! in_array($table, $this->tableNames($schema), strict: true)) {
            throw ToolException::tableNotFound($table);
        }

        $constraints = array_values($this->metadata->getConstraints($table, $schema));
        $primaryKey  = Constraints::primaryKey($constraints);
        $extras      = $this->extras->for($table, $schema);

        $columns = [];
        foreach ($this->metadata->getColumns($table, $schema) as $column) {
            $columns[] = $this->column($column, $primaryKey, $extras[$column->getName()] ?? null);
        }

        return new TableSchema($table, $schema, $columns, $primaryKey, Constraints::foreignKeys($constraints));
    }

    /**
     * @return list<string>
     */
    public function tableNames(?string $schema = null): array
    {
        return array_values($this->metadata->getTableNames($schema));
    }

    /**
     * @param list<string>                               $primaryKey
     * @param array{generated: bool, boolean: bool}|null $extra
     */
    private function column(ColumnObject $column, array $primaryKey, ?array $extra): ColumnSchema
    {
        $name     = $column->getName();
        $isKey    = in_array($name, $primaryKey, strict: true);
        $dataType = trim(strtolower((string) preg_replace(
            '/\(.*$/',
            replacement: '',
            subject: (string) $column->getDataType(),
        )));

        return new ColumnSchema(
            $name,
            $extra['boolean'] ?? false ? 'boolean' : $dataType,
            ! $isKey && ($column->getIsNullable() ?? true),
            $column->getColumnDefault(),
            $extra['generated'] ?? $this->sqliteRowId($dataType, $name, $primaryKey),
        );
    }

    /**
     * In SQLite an INTEGER PRIMARY KEY column aliases the auto-assigned row id.
     *
     * @param list<string> $primaryKey
     */
    private function sqliteRowId(string $dataType, string $name, array $primaryKey): bool
    {
        return Platform::Sqlite === $this->platform && 'integer' === $dataType && [$name] === $primaryKey;
    }
}
