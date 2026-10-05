<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Schema;

use PhpDb\Adapter\AdapterInterface;

use function is_array;
use function is_scalar;
use function str_contains;
use function str_starts_with;
use function strtolower;

/**
 * Column facts phpdb's metadata does not expose: which columns the
 * database generates (auto-increment, identity, serial) and, on MySQL,
 * which tinyint columns are booleans (tinyint(1)).
 *
 * @internal
 */
final readonly class ColumnExtras
{
    public function __construct(
        private AdapterInterface $adapter,
        private Platform $platform,
    ) {}

    /**
     * @return array<string, array{generated: bool, boolean: bool}> keyed by column name
     */
    public function for(string $table, ?string $schema): array
    {
        return match ($this->platform) {
            Platform::Mysql  => $this->mysql($table, $schema),
            Platform::Pgsql  => $this->pgsql($table, $schema ?? 'public'),
            Platform::Sqlite => [],
        };
    }

    /**
     * @return array<string, array{generated: bool, boolean: bool}>
     */
    private function mysql(string $table, ?string $schema): array
    {
        $extras = [];
        $rows   = $this->rows(
            'SELECT COLUMN_NAME AS name, EXTRA AS extra, COLUMN_TYPE AS type FROM information_schema.COLUMNS '
                . 'WHERE TABLE_NAME = ? AND TABLE_SCHEMA = COALESCE(?, DATABASE())',
            [$table, $schema],
        );
        foreach ($rows as $row) {
            $extras[$row['name'] ?? ''] = [
                'generated' => str_contains(strtolower($row['extra'] ?? ''), 'auto_increment'),
                'boolean'   => 'tinyint(1)' === strtolower($row['type'] ?? ''),
            ];
        }

        return $extras;
    }

    /**
     * @return array<string, array{generated: bool, boolean: bool}>
     */
    private function pgsql(string $table, string $schema): array
    {
        $extras = [];
        $rows   = $this->rows(
            'SELECT column_name AS name, is_identity AS identity, column_default AS "default" '
                . 'FROM information_schema.columns WHERE table_name = ? AND table_schema = ?',
            [$table, $schema],
        );
        foreach ($rows as $row) {
            $extras[$row['name'] ?? ''] = [
                'generated' => 'YES' === ($row['identity'] ?? '') || str_starts_with($row['default'] ?? '', 'nextval('),
                'boolean'   => false,
            ];
        }

        return $extras;
    }

    /**
     * Rows as string maps; null and non-scalar values become "".
     *
     * @param list<string|null> $parameters
     *
     * @return list<array<string, string>>
     *
     * @mago-expect analysis:mixed-assignment Driver rows are untyped; values are cast to strings here.
     */
    private function rows(string $sql, array $parameters): array
    {
        $rows = [];
        foreach ($this->adapter->getDriver()->createStatement($sql)->execute($parameters) ?? [] as $row) {
            $clean = [];
            foreach (is_array($row) ? $row : [] as $key => $value) {
                $clean[(string) $key] = is_scalar($value) ? (string) $value : '';
            }

            $rows[] = $clean;
        }

        return $rows;
    }
}
