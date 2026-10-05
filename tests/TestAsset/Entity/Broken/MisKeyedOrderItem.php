<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Mapping\Version;

/**
 * "order_items" keyed on the wrong column, with a generated #[Id] the
 * database does not generate.
 */
#[Table('order_items')]
final class MisKeyedOrderItem
{
    #[Id(generated: true), Column('user_id')]
    public ?int $userId = null;

    #[Version]
    public int $quantity = 1;
}
