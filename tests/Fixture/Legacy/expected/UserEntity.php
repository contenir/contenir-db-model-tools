<?php

declare(strict_types=1);

namespace Legacy\Entity;

use JsonSerializable;
use Contenir\Db\Model\Collection;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\HasMany;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\ManyToMany;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Mapping\Version;
use Contenir\Db\Model\Mapping\Via;
use DateTimeImmutable;

/**
 * A site user.
 */
#[Table('users')]
class UserEntity implements JsonSerializable
{
    public const ROLE_ADMIN = 'admin';

    #[Id(generated: true)]
    public ?int $id = null;

    #[Column]
    public string $email;

    #[Column]
    public bool $active = true;

    #[Column]
    public DateTimeImmutable $created_at;

    #[Column(type: 'date')]
    public ?DateTimeImmutable $birthday = null;

    #[Column(type: 'json')]
    public ?array $meta = null;

    #[Version]
    public int $version = 1;

    /** @var Collection<OrderItemEntity> */
    #[HasMany(OrderItemEntity::class, foreignKey: 'user_id', orderBy: ['quantity' => 'DESC'])]
    public Collection $orders;

    /** @var Collection<TagEntity> */
    #[ManyToMany(TagEntity::class, via: new Via('user_tag', foreignKey: 'user_id', relatedKey: 'tag_id', targetKey: 'id'))]
    public Collection $tags;

    public function isActive(): bool
    {
        return (bool) $this->active;
    }

    public function jsonSerialize(): array
    {
        return $this->getArrayCopy();
    }
}
