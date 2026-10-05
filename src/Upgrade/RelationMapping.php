<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Contenir\Db\Model\Mapping\BelongsTo;
use Contenir\Db\Model\Mapping\HasMany;
use Contenir\Db\Model\Mapping\HasOne;
use Contenir\Db\Model\Mapping\ManyToMany;
use Nette\PhpGenerator\Literal;

use function count;
use function sort;

/**
 * Chooses the 2.x relation attribute for a 1.x relation and its named
 * arguments (after the target class):
 *
 * - "via" → #[ManyToMany]
 * - single, keyed on this entity's primary key → #[HasOne]
 * - single, keyed on another column → #[BelongsTo]
 * - many → #[HasMany]
 *
 * Target-side keys are always written out, since the target's primary key
 * is not known while upgrading this entity.
 *
 * @internal
 */
final readonly class RelationMapping
{
    /**
     * @param class-string           $attribute
     * @param array<string, mixed>   $arguments
     */
    private function __construct(
        public string $attribute,
        public array $arguments,
    ) {}

    /**
     * @param list<string> $primaryKey the owning entity's key columns
     * @param string       $via        the short name to write for {@see \Contenir\Db\Model\Mapping\Via}
     */
    public static function for(LegacyRelation $relation, array $primaryKey, string $via): self
    {
        $keys     = $relation->keys;
        $ownKey   = self::same($keys->columns, $primaryKey);
        $criteria = self::criteria($relation->criteria);

        if (null !== $keys->viaTable) {
            $arguments = [
                $keys->viaTable,
                'foreignKey' => self::keys([] === $keys->viaColumns ? $keys->columns : $keys->viaColumns),
                'relatedKey' => self::keys([] === $keys->viaJoin ? $keys->targetColumns : $keys->viaJoin),
            ];
            $arguments += $ownKey ? [] : ['localKey' => self::keys($keys->columns)];

            return new self(ManyToMany::class, [
                'via' => new Literal("new {$via}(...?:)", [
                    $arguments + ['targetKey' => self::keys($keys->targetColumns)],
                ]),
                ...$criteria,
            ]);
        }

        if ($relation->single && ! $ownKey) {
            return new self(BelongsTo::class, [
                'foreignKey' => self::keys($keys->columns),
                'ownerKey'   => self::keys($keys->targetColumns),
            ]);
        }

        return new self($relation->single ? HasOne::class : HasMany::class, [
            'foreignKey' => self::keys($keys->targetColumns),
            ...($ownKey ? [] : ['localKey' => self::keys($keys->columns)]),
            ...$criteria,
        ]);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private static function criteria(LegacyCriteria $criteria): array
    {
        return [
            ...([] === $criteria->orderBy ? [] : ['orderBy' => $criteria->orderBy]),
            ...([] === $criteria->where ? [] : ['where' => $criteria->where]),
        ];
    }

    /**
     * @param non-empty-list<string> $columns
     *
     * @return string|non-empty-list<string>
     */
    private static function keys(array $columns): string|array
    {
        return 1 === count($columns) ? $columns[0] : $columns;
    }

    /**
     * @param list<string> $columns
     * @param list<string> $primaryKey
     */
    private static function same(array $columns, array $primaryKey): bool
    {
        sort($columns);
        sort($primaryKey);

        return [] !== $primaryKey && $columns === $primaryKey;
    }
}
