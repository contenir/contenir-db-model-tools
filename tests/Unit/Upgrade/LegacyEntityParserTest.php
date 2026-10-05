<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Tools\Exception\NotLegacyEntityException;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Exception\UpgradeException;
use Contenir\Db\Model\Tools\Upgrade\LegacyDefaults;
use Contenir\Db\Model\Tools\Upgrade\LegacyEntity;
use Contenir\Db\Model\Tools\Upgrade\LegacyEntityParser;
use Contenir\Db\Model\Tools\Upgrade\LegacySource;
use Contenir\Db\Model\Tools\Upgrade\RelationConfigReader;
use Contenir\Db\Model\Tools\Upgrade\TargetResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @mago-expect lint:no-literal-namespace-string Class names here are 1.x names that no longer exist.
 */
#[CoversClass(LegacyEntityParser::class)]
#[CoversClass(LegacyDefaults::class)]
#[CoversClass(LegacyEntity::class)]
#[CoversClass(LegacySource::class)]
#[CoversClass(NotLegacyEntityException::class)]
#[CoversClass(UpgradeException::class)]
#[CoversClass(ToolException::class)]
#[Group('unit')]
final class LegacyEntityParserTest extends TestCase
{
    private const string ENTITY = <<<'PHP'
        <?php
        namespace App\Entity;

        use Contenir\Db\Model\Entity\AbstractEntity;
        use App\Repository\GroupRepository as Groups;

        class UserEntity extends AbstractEntity
        {
            protected array $primaryKeys = ['id'];
            protected array $columns = ['id', 'group_id', 'version'];
            protected ?string $versionColumn = 'version';
            protected array $relations = [
                'group' => [
                    'type'   => self::RELATION_SINGLE,
                    'column' => 'group_id',
                    'table'  => ['class' => Groups::class, 'column' => 'id'],
                ],
                'logins' => [
                    'type'   => AbstractEntity::RELATION_MANY,
                    'column' => 'id',
                    'table'  => ['class' => \App\Repository\LoginRepository::class, 'column' => 'user_id'],
                ],
            ];
            protected $other;
        }
        PHP;

    /**
     * @return array<string, array{string, string}>
     */
    public static function unsupportedProvider(): array
    {
        return [
            'global constant' => ['[COLUMNS]', 'only literals, ::class and RELATION_* constants are supported'],
            'other constant'  => ['[self::OTHER]', 'unsupported constant OTHER'],
            'dynamic class'   => ['[$x::class]', 'dynamic class names are not supported'],
        ];
    }

    /**
     * @return array{LegacySource, LegacyEntity}
     */
    private static function parse(string $code): array
    {
        $notes = [];

        return (new LegacyEntityParser(new RelationConfigReader(new TargetResolver())))->parse(
            'User.php',
            $code,
            $notes,
        );
    }

    #[Test]
    public function missingSettingsAreEmpty(): void
    {
        [, $entity] = self::parse(
            "<?php\nclass Thing { protected \$columns; protected \$primaryKeys = []; protected \$versionColumn = ''; protected \$relations = 'none'; }",
        );

        static::assertSame(['Thing', '', 'Thing', [], [], null, []], [
            $entity->className,
            $entity->namespace(),
            $entity->shortName(),
            $entity->columns,
            $entity->primaryKeys,
            $entity->versionColumn,
            $entity->relations,
        ]);
    }

    #[Test]
    public function printsTheUnchangedSourceAsItWas(): void
    {
        [$source] = self::parse(self::ENTITY);

        static::assertSame(self::ENTITY, $source->print());
    }

    #[Test]
    public function readsTheMappingFromDefaults(): void
    {
        [, $entity] = self::parse(self::ENTITY);

        static::assertSame(
            ['App\\Entity\\UserEntity', 'App\\Entity', 'UserEntity', ['id', 'group_id', 'version'], ['id'], 'version'],
            [
                $entity->className,
                $entity->namespace(),
                $entity->shortName(),
                $entity->columns,
                $entity->primaryKeys,
                $entity->versionColumn,
            ],
        );
    }

    #[Test]
    public function rejectsFilesWithoutAnEntity(): void
    {
        $this->expectExceptionObject(NotLegacyEntityException::in('User.php'));

        self::parse("<?php\nclass Plain { public \$name; }\ninterface Other {}");
    }

    #[DataProvider('unsupportedProvider')]
    #[Test]
    public function rejectsNonConstantMapping(string $expression, string $reason): void
    {
        $this->expectExceptionObject(UpgradeException::unsupportedExpression('User.php', 'columns', $reason));

        self::parse("<?php\nclass UserEntity { protected \$columns = {$expression}; }");
    }

    #[Test]
    public function reportsFilesThatDoNotParse(): void
    {
        $this->expectException(ToolException::class);
        $this->expectExceptionMessageMatches('~^Cannot parse User\.php: Syntax error~');

        self::parse('<?php class {');
    }

    #[Test]
    public function resolvesImportedRepositoryClassesAndRelationTypes(): void
    {
        [, $entity] = self::parse(self::ENTITY);
        $relation = $entity->relations[0];

        static::assertSame(
            ['group', true, 'App\\Repository\\GroupRepository', 'App\\Entity\\GroupEntity'],
            [$relation->name, $relation->single, $relation->target->repository, $relation->target->entity],
        );
    }
}
