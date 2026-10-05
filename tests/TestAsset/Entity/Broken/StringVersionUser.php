<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Mapping\Version;
use DateTimeImmutable;

/**
 * "users" with #[Version] mapped to a non-integer column.
 */
#[Table('users')]
final class StringVersionUser
{
    #[Id(generated: true)]
    public ?int $id = null;

    #[Version, Column('email')]
    public int $emailVersion;

    #[Column('created_at')]
    public DateTimeImmutable $createdAt;
}
