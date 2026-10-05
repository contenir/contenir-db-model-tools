<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Generator;

use function array_key_exists;

/**
 * How entities are generated: target namespace, class-name overrides per
 * table, whether foreign keys become relations, and the version column.
 *
 * @api
 */
final readonly class EntityOptions
{
    /** @mago-expect lint:no-literal-namespace-string A namespace for generated code, not a class reference. */
    public const string DEFAULT_NAMESPACE = 'App\\Entity';

    /**
     * @param array<string, string> $classNames table => class short name overrides
     */
    public function __construct(
        public string $namespace = self::DEFAULT_NAMESPACE,
        public array $classNames = [],
        public bool $relations = true,
        public ?string $versionColumn = null,
    ) {}

    public function classFor(string $table): string
    {
        return array_key_exists($table, $this->classNames) ? $this->classNames[$table] : Inflector::className($table);
    }
}
