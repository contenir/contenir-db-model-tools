<?php

declare(strict_types=1);

namespace Legacy\Entity;

use Contenir\Db\Model\Entity\AbstractEntity;

class TagEntity extends AbstractEntity
{
    protected array $primaryKeys = ['id'];

    protected array $columns = ['id'];
}
