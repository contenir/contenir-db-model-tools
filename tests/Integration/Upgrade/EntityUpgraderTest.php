<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Integration\Upgrade;

use Contenir\Db\Model\Metadata\RelationKind;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Exception\UpgradeException;
use Contenir\Db\Model\Tools\Generator\GeneratedClass;
use Contenir\Db\Model\Tools\Schema\SchemaReader;
use Contenir\Db\Model\Tools\Upgrade\AstGrafter;
use Contenir\Db\Model\Tools\Upgrade\ClassMembers;
use Contenir\Db\Model\Tools\Upgrade\EntityUpgrader;
use Contenir\Db\Model\Tools\Upgrade\ImportFilter;
use Contenir\Db\Model\Tools\Upgrade\ImportMerger;
use Contenir\Db\Model\Tools\Upgrade\LegacyRelationRenderer;
use Contenir\Db\Model\Tools\Upgrade\LegacySource;
use Contenir\Db\Model\Tools\Upgrade\MappedClassBuilder;
use Contenir\Db\Model\Tools\Upgrade\MappingProperties;
use Contenir\Db\Model\Tools\Upgrade\PositionStripper;
use Contenir\Db\Model\Tools\Upgrade\UpgradeOptions;
use Contenir\Db\Model\Tools\Upgrade\UpgradeResult;
use ContenirTest\Db\Model\Tools\TestAsset\GeneratedClassLoader;
use ContenirTest\Db\Model\Tools\Trait\TestDatabaseTrait;
use org\bovigo\vfs\vfsStream;
use org\bovigo\vfs\vfsStreamDirectory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

use function array_map;
use function dirname;
use function file_get_contents;

#[CoversClass(EntityUpgrader::class)]
#[CoversClass(MappedClassBuilder::class)]
#[CoversClass(LegacyRelationRenderer::class)]
#[CoversClass(AstGrafter::class)]
#[CoversClass(ClassMembers::class)]
#[CoversClass(MappingProperties::class)]
#[CoversClass(ImportMerger::class)]
#[CoversClass(ImportFilter::class)]
#[CoversClass(PositionStripper::class)]
#[CoversClass(LegacySource::class)]
#[CoversClass(UpgradeOptions::class)]
#[CoversClass(UpgradeResult::class)]
#[CoversClass(UpgradeException::class)]
#[CoversClass(ToolException::class)]
#[Group('integration')]
final class EntityUpgraderTest extends TestCase
{
    use TestDatabaseTrait;

    private vfsStreamDirectory $root;

    /**
     * @return array<string, array{string}>
     */
    public static function fixtureProvider(): array
    {
        return [
            'columns, version, has-many and many-to-many' => ['UserEntity'],
            'belongs-to'                                  => ['OrderItemEntity'],
            'generated key only'                          => ['TagEntity'],
        ];
    }

    private static function fixture(string $path): string
    {
        return dirname(__DIR__, levels: 2) . "/Fixture/Legacy/{$path}";
    }

    #[Test]
    public function camelCasesPropertiesOnRequest(): void
    {
        $source = $this->upgrade('src/Entity/UserEntity.php', new UpgradeOptions(columnNames: false))->source;

        static::assertStringContainsString(
            "    #[Column('created_at')]\n    public DateTimeImmutable \$createdAt;\n",
            $source,
        );
    }

    #[Test]
    public function keepsOtherMembersAndImports(): void
    {
        vfsStream::newFile('Plain.php')->withContent(<<<'PHP'
            <?php

            namespace App\Entity;

            use Contenir\Db\Model\Mapping\Id;

            class Plain
            {
                protected $columns = ['id', 'user_id'], $label = 'x';
                protected $relations = [
                    'user' => [
                        'type'   => 'single',
                        'column' => 'user_id',
                        'table'  => ['class' => 'App\Repository\UserRepository', 'column' => 'id'],
                        'order'  => ['id'],
                    ],
                ];
            }
            PHP)->at($this->root);

        $result = $this->upgrade('Plain.php', new UpgradeOptions(table: 'order_items'));

        static::assertSame(
            [
                <<<'PHP'
                    <?php

                    namespace App\Entity;

                    use Contenir\Db\Model\Mapping\Id;
                    use Contenir\Db\Model\Mapping\BelongsTo;
                    use Contenir\Db\Model\Mapping\Column;
                    use Contenir\Db\Model\Mapping\Table;
                    use Contenir\Db\Model\Relation\LazyRelationsTrait;

                    #[Table('order_items')]
                    class Plain
                    {
                        use LazyRelationsTrait;

                        #[Id]
                        public int $id;

                        #[Column]
                        public ?int $user_id = null;

                        #[BelongsTo(UserEntity::class, foreignKey: 'user_id', ownerKey: 'id')]
                        public ?UserEntity $user;

                        protected $label = 'x';
                    }
                    PHP,
                ['relation $user: where/order were dropped; #[BelongsTo] does not take them'],
            ],
            [$result->source, $result->notes],
        );
    }

    #[Test]
    public function needsTheTableWithoutALegacyRepository(): void
    {
        vfsStream::newFile('Entity/LooseEntity.php')->withContent(
            "<?php\nclass LooseEntity { protected \$columns = ['id']; }",
        )->at($this->root);

        $this->expectExceptionObject(UpgradeException::tableUnknown('LooseEntity'));

        $this->upgrade('Entity/LooseEntity.php');
    }

    #[Test]
    public function notesWhatNeedsAttention(): void
    {
        static::assertSame(
            [
                'relation $orders: where condition "quantity > 0" was dropped; 2.x supports column => value equality only',
                'method jsonSerialize() uses the 1.x entity API; rewrite it for typed properties',
            ],
            $this->upgrade('src/Entity/UserEntity.php')->notes,
        );
    }

    #[Test]
    public function rejectsColumnsTheTableLacks(): void
    {
        vfsStream::newFile('Odd.php')->withContent(
            "<?php\nclass Odd { protected \$columns = ['id', 'nickname']; }",
        )->at($this->root);

        $this->expectExceptionObject(UpgradeException::columnNotFound('Odd', 'nickname', 'tags'));

        $this->upgrade('Odd.php', new UpgradeOptions(table: 'tags'));
    }

    #[Test]
    public function reportsMissingFiles(): void
    {
        $this->expectExceptionObject(ToolException::cannotRead('vfs://root/Missing.php', 'no such file'));

        $this->upgrade('Missing.php');
    }

    /**
     * @mago-expect lint:no-literal-namespace-string The fixtures' namespace only exists as parsed source.
     */
    #[Test]
    public function upgradedEntitiesAreValidMappings(): void
    {
        $classes = array_map(
            fn(string $class): GeneratedClass => new GeneratedClass(
                $class,
                $this->upgrade("src/Entity/{$class}.php")->source,
            ),
            ['UserEntity', 'OrderItemEntity', 'TagEntity'],
        );
        $metadata = GeneratedClassLoader::load($classes, 'Legacy\\Entity');

        static::assertSame(
            [RelationKind::HasMany, RelationKind::ManyToMany, RelationKind::BelongsTo, 'version'],
            [
                $metadata['UserEntity']->getRelation('orders')->kind,
                $metadata['UserEntity']->getRelation('tags')->kind,
                $metadata['OrderItemEntity']->getRelation('user')->kind,
                $metadata['UserEntity']->version?->columnName,
            ],
        );
    }

    #[Test]
    public function upgradesClassesOutsideANamespace(): void
    {
        vfsStream::newFile('Thing.php')->withContent(<<<'PHP'
            <?php

            declare(strict_types=1);

            class Thing extends Base implements \Contenir\Db\Model\Entity\EntityInterface, Countable
            {
                protected $primaryKeys = ['id'];
                protected $columns = ['id'];

                public function count(): int
                {
                    return 0;
                }
            }
            PHP)->at($this->root);

        $result = $this->upgrade('Thing.php', new UpgradeOptions(table: 'tags'));

        static::assertSame(
            [
                <<<'PHP'
                    <?php

                    declare(strict_types=1);

                    use Contenir\Db\Model\Mapping\Id;
                    use Contenir\Db\Model\Mapping\Table;

                    #[Table('tags')]
                    class Thing extends Base implements Countable
                    {
                        #[Id(generated: true)]
                        public ?int $id = null;

                        public function count(): int
                        {
                            return 0;
                        }
                    }
                    PHP,
                ['still extends Base; upgrade that class too, or remove it'],
            ],
            [$result->source, $result->notes],
        );
    }

    #[DataProvider('fixtureProvider')]
    #[Test]
    public function upgradesInPlaceKeepingEverythingElse(string $class): void
    {
        static::assertSame(
            file_get_contents(self::fixture("expected/{$class}.php")),
            $this->upgrade("src/Entity/{$class}.php")->source,
        );
    }

    protected function setUp(): void
    {
        $this->setUpTestDatabase();
        $this->root = vfsStream::setup();
        vfsStream::copyFromFileSystem(self::fixture('src'), vfsStream::newDirectory('src')->at($this->root));
    }

    private function upgrade(string $file, UpgradeOptions $options = new UpgradeOptions()): UpgradeResult
    {
        return (new EntityUpgrader(new SchemaReader($this->adapter), $options))->upgrade(
            "{$this->root->url()}/{$file}",
        );
    }
}
