<?php

declare(strict_types=1);

namespace Legacy\Entity;

use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;

#[Table('tags')]
class TagEntity
{
    #[Id(generated: true)]
    public ?int $id = null;
}
