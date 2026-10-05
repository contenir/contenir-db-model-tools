<?php

declare(strict_types=1);

namespace Legacy\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;
use Legacy\Repository\OrderItemRepository;
use Legacy\Repository\TagRepository;
use JsonSerializable;

/**
 * A site user.
 */
class UserEntity extends AbstractEntity implements JsonSerializable
{
    public const ROLE_ADMIN = 'admin';

    protected array $primaryKeys = ['id'];

    protected array $columns = [
        'id',
        'email',
        'active',
        'created_at',
        'birthday',
        'meta',
        'version',
    ];

    protected ?string $versionColumn = 'version';

    protected array $relations = [
        'orders' => [
            'type'   => self::RELATION_MANY,
            'column' => 'id',
            'table'  => ['class' => OrderItemRepository::class, 'column' => 'user_id'],
            'order'  => ['quantity DESC'],
            'where'  => ['quantity > 0'],
        ],
        'tags' => [
            'type'   => AbstractEntity::RELATION_MANY,
            'column' => 'id',
            'table'  => ['class' => TagRepository::class, 'column' => 'id'],
            'via'    => ['table' => 'user_tag', 'column' => 'user_id', 'join' => 'tag_id'],
        ],
    ];

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
