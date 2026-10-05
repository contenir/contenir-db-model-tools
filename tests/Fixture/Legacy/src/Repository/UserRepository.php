<?php

declare(strict_types=1);

namespace Legacy\Repository;

use Contenir\Db\Model\Repository\BaseRepository;

class UserRepository extends BaseRepository
{
    protected $table = 'users';
}
