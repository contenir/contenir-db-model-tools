<?php

declare(strict_types=1);

namespace Legacy\Repository;

use Contenir\Db\Model\Repository\BaseRepository;

class OrderItemRepository extends BaseRepository
{
    protected $table = ['oi' => 'order_items'];
}
