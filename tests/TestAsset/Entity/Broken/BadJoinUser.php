<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Entity\Broken;

use Contenir\Db\Model\Collection;
use Contenir\Db\Model\Mapping\Column;
use Contenir\Db\Model\Mapping\Id;
use Contenir\Db\Model\Mapping\ManyToMany;
use Contenir\Db\Model\Mapping\Table;
use Contenir\Db\Model\Mapping\Via;
use ContenirTest\Db\Model\Tools\TestAsset\Entity\Tag;
use DateTimeImmutable;

/**
 * "users" with one join table that does not exist and one with a wrong column.
 */
#[Table('users')]
final class BadJoinUser
{
    #[Id(generated: true)]
    public ?int $id = null;

    #[Column]
    public string $email;

    #[Column('created_at')]
    public DateTimeImmutable $createdAt;

    /**
     * @var Collection<Tag>
     */
    #[ManyToMany(Tag::class, via: new Via('user_label', foreignKey: 'user_id', relatedKey: 'label_id'))]
    public Collection $labels;

    /**
     * @var Collection<Tag>
     */
    #[ManyToMany(Tag::class, via: new Via('user_tag', foreignKey: 'user_id', relatedKey: 'label_id'))]
    public Collection $tags;
}
