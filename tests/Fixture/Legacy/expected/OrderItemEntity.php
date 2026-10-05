<?php

declare(strict_types=1);

namespace Legacy\Entity;

use Contenir\Db\Model\Mapping\BelongsTo;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Relation\LazyRelationsTrait;

#[Table('order_items')]
class OrderItemEntity
{
    use LazyRelationsTrait;

    #[Id]
    public int $id;

    #[Column]
    public ?int $user_id = null;

    #[Column]
    public int $quantity = 1;

    #[BelongsTo(UserEntity::class, foreignKey: 'user_id', ownerKey: 'id')]
    public ?UserEntity $user;
}
