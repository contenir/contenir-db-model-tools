<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken;

use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\Table;
use DateTimeImmutable;

/**
 * "users" with drifted columns: a missing column, a nullability mismatch
 * each way, a type mismatch and an #[Id] that should be generated.
 */
#[Table('users')]
final class DriftedUser
{
    #[Id]
    public int $id;

    #[Column]
    public ?string $email = null;

    #[Column('created_at')]
    public int $createdAt;

    #[Column]
    public DateTimeImmutable $birthday;

    #[Column]
    public string $nickname;
}
