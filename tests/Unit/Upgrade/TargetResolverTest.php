<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Tools\Upgrade\TargetResolver;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

/**
 * @mago-expect lint:no-literal-namespace-string Class names here are 1.x names that no longer exist.
 */
#[CoversClass(TargetResolver::class)]
#[Group('unit')]
final class TargetResolverTest extends TestCase
{
    /**
     * @return array<string, array{string, string}>
     */
    public static function conventionProvider(): array
    {
        return [
            'namespace and suffix' => ['App\\Repository\\UserRepository', 'App\\Entity\\UserEntity'],
            'leading backslash'    => ['\\App\\Repository\\UserRepository', 'App\\Entity\\UserEntity'],
            'suffix only'          => ['App\\Model\\UserRepository', 'App\\Model\\UserEntity'],
            'unconventional'       => ['App\\Users', 'App\\Users'],
        ];
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function repositoryProvider(): array
    {
        return [
            'conventional'   => ['App\\Entity\\UserEntity', 'App\\Repository\\UserRepository'],
            'unconventional' => ['App\\Model\\User', 'App\\Model\\UserRepository'],
        ];
    }

    #[Test]
    public function explicitMapWins(): void
    {
        $resolver = new TargetResolver(['App\\Repository\\UserRepository' => '\\App\\Model\\User']);

        static::assertSame('App\\Model\\User', $resolver->resolve('\\App\\Repository\\UserRepository'));
    }

    #[DataProvider('conventionProvider')]
    #[Test]
    public function followsTheLegacyNamingConvention(string $repository, string $entity): void
    {
        static::assertSame($entity, (new TargetResolver())->resolve($repository));
    }

    #[DataProvider('repositoryProvider')]
    #[Test]
    public function pairsAnEntityWithItsRepository(string $entity, string $repository): void
    {
        static::assertSame($repository, TargetResolver::repositoryFor($entity));
    }
}
