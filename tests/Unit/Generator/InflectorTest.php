<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Generator;

use Contenir\Db\Model\Tools\Generator\Inflector;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(Inflector::class)]
#[Group('unit')]
final class InflectorTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function classNameProvider(): array
    {
        return [
            'plural'        => ['users', 'User'],
            'snake plural'  => ['order_items', 'OrderItem'],
            'ies'           => ['categories', 'Category'],
            'sses'          => ['addresses', 'Address'],
            'ches'          => ['matches', 'Match'],
            'xes'           => ['boxes', 'Box'],
            'uses'          => ['statuses', 'Status'],
            'ss kept'       => ['access', 'Access'],
            'is kept'       => ['analysis', 'Analysis'],
            'singular kept' => ['user_tag', 'UserTag'],
            'odd chars'     => ['user-profile data', 'UserProfileData'],
        ];
    }

    #[Test]
    public function columnBecomesCamelCaseProperty(): void
    {
        static::assertSame(['createdAt', 'id', 'userId'], [
            Inflector::property('created_at'),
            Inflector::property('ID'),
            Inflector::property('user_id'),
        ]);
    }

    #[Test]
    public function recognisesNamesUsableAsProperties(): void
    {
        static::assertSame([true, true, false, false], [
            Inflector::isIdentifier('created_at'),
            Inflector::isIdentifier('_x1'),
            Inflector::isIdentifier('first-name'),
            Inflector::isIdentifier('1st'),
        ]);
    }

    #[Test]
    public function relationIsNamedAfterForeignKeyOrReferencedTable(): void
    {
        static::assertSame(['author', 'category', 'category'], [
            Inflector::relation(['author_id'], 'users'),
            Inflector::relation(['category_ref'], 'categories'),
            Inflector::relation(['group_id', 'category_id'], 'categories'),
        ]);
    }

    #[DataProvider('classNameProvider')]
    #[Test]
    public function tableBecomesSingularPascalCaseClass(string $table, string $class): void
    {
        static::assertSame($class, Inflector::className($table));
    }
}
