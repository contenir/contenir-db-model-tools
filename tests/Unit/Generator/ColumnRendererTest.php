<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Generator;

use Contenir\Db\Model\Tools\Generator\ColumnRenderer;
use Contenir\Db\Model\Tools\Schema\ColumnSchema;
use Contenir\Db\Model\Tools\Schema\TableSchema;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\PhpNamespace;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ColumnRenderer::class)]
#[Group('unit')]
final class ColumnRendererTest extends TestCase
{
    /**
     * @return array<string, array{bool, string, string}>
     */
    public static function namingProvider(): array
    {
        return [
            'camel case'           => [false, 'created_at', 'createdAt'],
            'column name kept'     => [true, 'created_at', 'created_at'],
            'unusable column name' => [true, 'first-name', 'firstName'],
        ];
    }

    #[DataProvider('namingProvider')]
    #[Test]
    public function namesPropertiesAfterColumns(bool $columnNames, string $column, string $property): void
    {
        $schema = new ColumnSchema($column, 'varchar', false);
        $table  = new TableSchema('t', null, [$schema], []);

        static::assertSame(
            $property,
            (new ColumnRenderer(null, $columnNames))->render(
                new PhpNamespace('App'),
                new ClassType('T'),
                $table,
                $schema,
            ),
        );
    }
}
