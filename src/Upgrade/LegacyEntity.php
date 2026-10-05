<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use function strrpos;
use function substr;

/**
 * The mapping a 1.x entity declared in $columns, $primaryKeys,
 * $versionColumn and $relations.
 *
 * @internal
 */
final readonly class LegacyEntity
{
    /**
     * @param string                $className   fully qualified
     * @param list<string>          $columns
     * @param list<string>          $primaryKeys
     * @param list<LegacyRelation>  $relations
     */
    public function __construct(
        public string $className,
        public array $columns,
        public array $primaryKeys,
        public ?string $versionColumn,
        public array $relations,
    ) {}

    public function namespace(): string
    {
        $separator = strrpos($this->className, needle: '\\');

        return false === $separator ? '' : substr($this->className, offset: 0, length: $separator);
    }

    public function shortName(): string
    {
        $separator = strrpos($this->className, needle: '\\');

        return false === $separator ? $this->className : substr($this->className, $separator + 1);
    }
}
