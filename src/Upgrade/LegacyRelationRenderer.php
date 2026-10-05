<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Contenir\Db\Model\Collection;
use Contenir\Db\Model\Mapping\BelongsTo;
use Contenir\Db\Model\Mapping\Via;
use Contenir\Db\Model\Relation\LazyRelationsTrait;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use Nette\PhpGenerator\PhpNamespace;

use function array_key_exists;
use function sprintf;

/**
 * Adds a typed, attributed property for each 1.x relation. Single
 * relations are nullable (1.x returned false for a missing row) and need
 * LazyRelationsTrait to load lazily.
 *
 * @internal
 */
final readonly class LegacyRelationRenderer
{
    /**
     * @param list<string> $notes receives notes on settings that cannot be carried over
     */
    public static function render(PhpNamespace $namespace, ClassType $class, LegacyEntity $entity, array &$notes): void
    {
        foreach ($entity->relations as $relation) {
            if (null !== $relation->keys->viaTable) {
                $namespace->addUse(Via::class);
            }

            $target  = $namespace->simplifyName($relation->target->entity);
            $mapping = RelationMapping::for($relation, $entity->primaryKeys, $namespace->simplifyName(Via::class));
            $namespace->addUse($mapping->attribute);

            $property = $class->addProperty($relation->name);
            $property->addAttribute($mapping->attribute, [new Literal("{$target}::class"), ...$mapping->arguments]);
            if (! $relation->single) {
                $namespace->addUse(Collection::class);
                $property->setType(Collection::class)->addComment("@var Collection<{$target}>");

                continue;
            }

            $property->setType($relation->target->entity)->setNullable();
            if (! array_key_exists(LazyRelationsTrait::class, $class->getTraits())) {
                $namespace->addUse(LazyRelationsTrait::class);
                $class->addTrait(LazyRelationsTrait::class);
            }

            if (BelongsTo::class === $mapping->attribute && ! $relation->criteria->isEmpty()) {
                $notes[] = sprintf(
                    'relation $%s: where/order were dropped; #[BelongsTo] does not take them',
                    $relation->name,
                );
            }
        }
    }
}
