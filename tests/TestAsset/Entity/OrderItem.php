<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity;

use Contenir\Db\Model\Mapping\BelongsTo;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * Matches the fixture "order_items" table exactly.
 */
#[Table('order_items')]
final class OrderItem
{
    #[Id]
    public int $id;

    #[Column('user_id')]
    public ?int $userId = null;

    #[Column]
    public int $quantity = 1;

    #[BelongsTo(User::class, foreignKey: 'user_id')]
    public ?User $user;
}
