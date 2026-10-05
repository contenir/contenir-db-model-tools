<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Tools\Exception\UpgradeException;
use Contenir\Db\Model\Tools\Upgrade\LegacyCriteria;
use Contenir\Db\Model\Tools\Upgrade\LegacyKeys;
use Contenir\Db\Model\Tools\Upgrade\LegacyRelation;
use Contenir\Db\Model\Tools\Upgrade\LegacyTarget;
use Contenir\Db\Model\Tools\Upgrade\RelationConfigReader;
use Contenir\Db\Model\Tools\Upgrade\StringList;
use Contenir\Db\Model\Tools\Upgrade\TargetResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @mago-expect lint:no-literal-namespace-string Class names here are 1.x names that no longer exist.
 */
#[CoversClass(RelationConfigReader::class)]
#[CoversClass(LegacyKeys::class)]
#[CoversClass(LegacyRelation::class)]
#[CoversClass(LegacyTarget::class)]
#[CoversClass(StringList::class)]
#[CoversClass(UpgradeException::class)]
#[Group('unit')]
final class RelationConfigReaderTest extends TestCase
{
    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidProvider(): array
    {
        return [
            'not an array'   => ['orders'],
            'no column'      => [['table' => ['class' => 'X']]],
            'no table'       => [['column' => 'id']],
            'no table class' => [['column' => 'id', 'table' => ['column' => 'id']]],
        ];
    }

    /**
     * @param array<array-key, mixed> $relations
     *
     * @return list<LegacyRelation>
     */
    private static function read(array $relations): array
    {
        $notes = [];

        return (new RelationConfigReader(new TargetResolver()))->read('App\\Entity\\UserEntity', $relations, $notes);
    }

    #[Test]
    public function readsAManyRelationWithDefaults(): void
    {
        static::assertEquals(
            [new LegacyRelation(
                'orders',
                false,
                new LegacyTarget('App\\Entity\\OrderEntity', 'App\\Repository\\OrderRepository'),
                new LegacyKeys(['id'], ['user_id']),
                new LegacyCriteria(),
            )],
            self::read([
                'orders' => [
                    'column' => 'id',
                    'table'  => ['class' => 'App\\Repository\\OrderRepository', 'column' => 'user_id'],
                ],
            ]),
        );
    }

    #[Test]
    public function readsASingleRelationViaAJoinTable(): void
    {
        static::assertEquals(
            new LegacyKeys(['id', 'site'], ['id', 'site'], 'user_role', ['user_id', 'user_site'], ['role_id']),
            self::read([
                'role' => [
                    'type'   => 'single',
                    'column' => ['id', 'site', ''],
                    'table'  => ['class' => 'App\\Repository\\RoleRepository'],
                    'via'    => ['table' => 'user_role', 'column' => ['user_id', 'user_site'], 'join' => 'role_id'],
                ],
            ])[0]->keys,
        );
    }

    #[DataProvider('invalidProvider')]
    #[Test]
    public function rejectsRelationsWithoutColumnOrTarget(mixed $config): void
    {
        $this->expectExceptionObject(UpgradeException::invalidRelation(
            'App\\Entity\\UserEntity',
            'orders',
            '"column" and "table.class" are required',
        ));

        self::read(['orders' => $config]);
    }
}
