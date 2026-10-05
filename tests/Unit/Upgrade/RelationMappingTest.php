<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Mapping\BelongsTo;
use Contenir\Db\Model\Mapping\HasMany;
use Contenir\Db\Model\Mapping\HasOne;
use Contenir\Db\Model\Mapping\ManyToMany;
use Contenir\Db\Model\Tools\Upgrade\LegacyCriteria;
use Contenir\Db\Model\Tools\Upgrade\LegacyKeys;
use Contenir\Db\Model\Tools\Upgrade\LegacyRelation;
use Contenir\Db\Model\Tools\Upgrade\LegacyTarget;
use Contenir\Db\Model\Tools\Upgrade\RelationMapping;
use Nette\PhpGenerator\Dumper;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function preg_replace;

#[CoversClass(RelationMapping::class)]
#[Group('unit')]
final class RelationMappingTest extends TestCase
{
    /**
     * @return array<string, array{LegacyRelation, string, string}>
     */
    public static function mappingProvider(): array
    {
        $ordered = new LegacyCriteria(['live' => true], ['name' => 'ASC']);

        return [
            'many on own key'       => [
                self::relation(false, new LegacyKeys(['id'], ['user_id']), $ordered),
                HasMany::class,
                "['foreignKey' => 'user_id', 'orderBy' => ['name' => 'ASC'], 'where' => ['live' => true]]",
            ],
            'many on other column'  => [
                self::relation(false, new LegacyKeys(['code'], ['user_code'])),
                HasMany::class,
                "['foreignKey' => 'user_code', 'localKey' => 'code']",
            ],
            'single on own key'     => [
                self::relation(true, new LegacyKeys(['id'], ['user_id'])),
                HasOne::class,
                "['foreignKey' => 'user_id']",
            ],
            'single on foreign key' => [
                self::relation(true, new LegacyKeys(['group_id', 'site'], ['id', 'site'])),
                BelongsTo::class,
                "['foreignKey' => ['group_id', 'site'], 'ownerKey' => ['id', 'site']]",
            ],
            'via with defaults'     => [
                self::relation(false, new LegacyKeys(['id'], ['id'], 'user_tag')),
                ManyToMany::class,
                "['via' => new Via('user_tag', foreignKey: 'id', relatedKey: 'id', targetKey: 'id')]",
            ],
            'via on other column'   => [
                self::relation(
                    false,
                    new LegacyKeys(['code'], ['id'], 'user_tag', ['user_code'], ['tag_id']),
                    $ordered,
                ),
                ManyToMany::class,
                "['via' => new Via('user_tag', foreignKey: 'user_code', relatedKey: 'tag_id', localKey: 'code', targetKey: 'id'), "
                    . "'orderBy' => ['name' => 'ASC'], 'where' => ['live' => true]]",
            ],
        ];
    }

    private static function relation(
        bool $single,
        LegacyKeys $keys,
        LegacyCriteria $criteria = new LegacyCriteria(),
    ): LegacyRelation {
        return new LegacyRelation('rel', $single, new LegacyTarget('T', 'R'), $keys, $criteria);
    }

    #[DataProvider('mappingProvider')]
    #[Test]
    public function choosesAttributeAndArguments(LegacyRelation $relation, string $attribute, string $arguments): void
    {
        $mapping = RelationMapping::for($relation, ['id'], 'Via');

        $dumped = preg_replace(
            ['/\[\s+/', '/,\s+\]/', '/,\s+/'],
            replacement: ['[', ']', ', '],
            subject: (new Dumper())->dump($mapping->arguments),
        );

        static::assertSame([$attribute, $arguments], [$mapping->attribute, $dumped]);
    }

    #[Test]
    public function tableWithoutKeyNeverMatchesOwnKey(): void
    {
        $relation = self::relation(true, new LegacyKeys(['id'], ['user_id']));

        static::assertSame(BelongsTo::class, RelationMapping::for($relation, [], 'Via')->attribute);
    }
}
