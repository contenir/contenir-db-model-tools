<?php

declare(strict_types=1);

namespace Legacy\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;
use Legacy\Repository\UserRepository;

class OrderItemEntity extends AbstractEntity
{
    protected array $primaryKeys = ['id'];

    protected array $columns = ['id', 'user_id', 'quantity'];

    protected array $relations = [
        'user' => [
            'type'   => AbstractEntity::RELATION_SINGLE,
            'column' => 'user_id',
            'table'  => ['class' => UserRepository::class, 'column' => 'id'],
        ],
    ];
}
