<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity;

use Contenir\Db\Model\Collection;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\ManyToMany;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Mapping\Version;
use Contenir\Db\Model\Mapping\Via;
use DateTimeImmutable;

/**
 * Matches the fixture "users" table exactly.
 */
#[Table('users')]
final class User
{
    #[Id(generated: true)]
    public ?int $id = null;

    #[Column]
    public string $email;

    #[Column]
    public bool $active = true;

    #[Column('created_at')]
    public DateTimeImmutable $createdAt;

    #[Column(type: 'date')]
    public ?DateTimeImmutable $birthday = null;

    #[Column(type: 'json')]
    public ?array $meta = null;

    #[Column]
    public ?string $price = null;

    #[Column]
    public float $score = 1.5;

    #[Version]
    public int $version = 1;

    /**
     * @var Collection<Tag>
     */
    #[ManyToMany(Tag::class, via: new Via('user_tag', foreignKey: 'user_id', relatedKey: 'tag_id'))]
    public Collection $tags;
}
