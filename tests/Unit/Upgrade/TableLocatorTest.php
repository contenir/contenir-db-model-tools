<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Tools\Upgrade\TableLocator;
use Contenir\Db\Model\Tools\Upgrade\TargetResolver;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @mago-expect lint:no-literal-namespace-string Class names here are 1.x names that no longer exist.
 */
#[CoversClass(TableLocator::class)]
#[CoversClass(TargetResolver::class)]
#[Group('unit')]
final class TableLocatorTest extends TestCase
{
    /**
     * @return array<string, array{string, string|null}>
     */
    public static function repositoryProvider(): array
    {
        return [
            'string table'      => ['protected $table = "users";', 'users'],
            'aliased table'     => ["protected \$table = ['u' => 'users'];", 'users'],
            'table identifier'  => ["protected \$table = new TableIdentifier('users');", null],
            'no table property' => ['protected $other = "users";', null],
            'empty array'       => ['protected $table = [];', null],
            'no default'        => ['protected $table;', null],
        ];
    }

    #[Test]
    public function ignoresRepositoriesThatDoNotParse(): void
    {
        $root = vfsStream::setup('root', null, [
            'Entity'     => ['UserEntity.php' => ''],
            'Repository' => ['UserRepository.php' => '<?php class {'],
        ]);

        static::assertNull(TableLocator::find("{$root->url()}/Entity/UserEntity.php", 'App\\Entity\\UserEntity'));
    }

    #[Test]
    public function needsAnEntityDirectory(): void
    {
        static::assertNull(TableLocator::find('/app/src/Model/UserEntity.php', 'App\\Model\\UserEntity'));
    }

    #[Test]
    public function needsTheRepositoryFile(): void
    {
        $root = vfsStream::setup('root', null, ['Entity' => ['UserEntity.php' => '']]);

        static::assertNull(TableLocator::find("{$root->url()}/Entity/UserEntity.php", 'App\\Entity\\UserEntity'));
    }

    #[DataProvider('repositoryProvider')]
    #[Test]
    public function readsTheTableFromThePairedRepository(string $property, ?string $table): void
    {
        $root = vfsStream::setup('root', null, [
            'src' => [
                'Entity'     => ['Admin' => ['UserEntity.php' => '']],
                'Repository' => ['Admin' => ['UserRepository.php' => "<?php\nclass UserRepository { {$property} }"]],
            ],
        ]);

        static::assertSame(
            $table,
            TableLocator::find("{$root->url()}/src/Entity/Admin/UserEntity.php", 'App\\Entity\\Admin\\UserEntity'),
        );
    }
}
