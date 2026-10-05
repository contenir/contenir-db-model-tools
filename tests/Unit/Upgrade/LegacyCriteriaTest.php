<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Tools\Upgrade\LegacyCriteria;
use Contenir\Db\Model\Tools\Upgrade\LegacyOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function count;

#[CoversClass(LegacyCriteria::class)]
#[CoversClass(LegacyOrder::class)]
#[Group('unit')]
final class LegacyCriteriaTest extends TestCase
{
    /**
     * @return array<string, array{mixed, array<string, string>, int}>
     */
    public static function orderProvider(): array
    {
        return [
            'string with direction' => [['created_at DESC'], ['created_at' => 'DESC'], 0],
            'bare column'           => [['name'], ['name' => 'ASC'], 0],
            'single string'         => ['name   desc', ['name' => 'DESC'], 0],
            'column => direction'   => [['name' => 'desc'], ['name' => 'DESC'], 0],
            'bad direction'         => [['name sideways', 'id' => 'up', 'x' => 1], [], 3],
            'not a string'          => [[5], [], 1],
            'none'                  => [[], [], 0],
        ];
    }

    #[DataProvider('orderProvider')]
    #[Test]
    public function convertsOrder(mixed $order, array $orderBy, int $dropped): void
    {
        $notes    = [];
        $criteria = LegacyCriteria::read('items', ['order' => $order], $notes);

        static::assertSame([$orderBy, $dropped], [$criteria->orderBy, count($notes)]);
    }

    #[Test]
    public function emptinessReflectsWhereAndOrder(): void
    {
        static::assertSame(
            [true, false, false],
            [
                (new LegacyCriteria())->isEmpty(),
                (new LegacyCriteria(where: ['a' => 1]))->isEmpty(),
                (new LegacyCriteria(orderBy: ['a' => 'ASC']))->isEmpty(),
            ],
        );
    }

    #[Test]
    public function keepsEqualityConditionsAndNotesTheRest(): void
    {
        $notes    = [];
        $criteria = LegacyCriteria::read(
            'items',
            ['where' => ['status' => 'live', 'deleted' => null, 'qty > 0', 'tags' => [1]]],
            $notes,
        );

        static::assertSame(
            [
                ['status' => 'live', 'deleted' => null],
                [
                    'relation $items: where condition "qty > 0" was dropped; 2.x supports column => value equality only',
                    'relation $items: where condition "tags" was dropped; 2.x supports column => value equality only',
                ],
            ],
            [$criteria->where, $notes],
        );
    }

    #[Test]
    public function readsASingleWhereCondition(): void
    {
        $notes = [];
        LegacyCriteria::read('items', ['where' => 'qty > 0'], $notes);

        static::assertSame(
            ['relation $items: where condition "qty > 0" was dropped; 2.x supports column => value equality only'],
            $notes,
        );
    }
}
