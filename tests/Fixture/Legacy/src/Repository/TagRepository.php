<?php

declare(strict_types=1);

namespace Legacy\Repository;

use Contenir\Db\Model\Repository\BaseRepository;

class TagRepository extends BaseRepository
{
    protected $table = 'tags';
}
