<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use function array_key_exists;
use function ltrim;
use function str_ends_with;
use function str_replace;
use function substr;

/**
 * Maps a 1.x relation's repository class to its entity class: through an
 * explicit map (1.x "model.map", flipped), or by 1.x's convention
 * (…\Repository\UserRepository → …\Entity\UserEntity).
 *
 * @internal
 */
final readonly class TargetResolver
{
    /**
     * @param array<string, string> $map repository class => entity class
     */
    public function __construct(
        private array $map = [],
    ) {}

    /**
     * The 1.x repository conventionally paired with an entity class.
     */
    public static function repositoryFor(string $entity): string
    {
        $repository = str_replace(
            search: '\\Entity\\',
            replace: '\\Repository\\',
            subject: $entity,
        );

        return str_ends_with($repository, 'Entity')
            ? substr($repository, offset: 0, length: -6) . 'Repository'
            : "{$repository}Repository";
    }

    public function resolve(string $repository): string
    {
        $repository = ltrim($repository, characters: '\\');
        if (array_key_exists($repository, $this->map)) {
            return ltrim($this->map[$repository], characters: '\\');
        }

        $entity = str_replace(
            search: '\\Repository\\',
            replace: '\\Entity\\',
            subject: $repository,
        );

        return str_ends_with($entity, 'Repository') ? substr($entity, offset: 0, length: -10) . 'Entity' : $entity;
    }
}
