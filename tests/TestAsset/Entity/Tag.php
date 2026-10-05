<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity;

use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

/**
 * Relation target only; the fixture schema has no "tags" table.
 */
#[Table('tags')]
final class Tag
{
    #[Id(generated: true)]
    public ?int $id = null;
}
