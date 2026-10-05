<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken;

use Contenir\Db\Model\Mapping\Table;

/**
 * Invalid mapping: no #[Id].
 */
#[Table('users')]
final class Unmapped
{
    #[Column]
    public int $id;
}
