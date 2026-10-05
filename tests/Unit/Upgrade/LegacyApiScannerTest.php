<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Upgrade;

use Contenir\Db\Model\Tools\Upgrade\LegacyApiScanner;
use PhpParser\Node\Stmt\Class_;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(LegacyApiScanner::class)]
#[Group('unit')]
final class LegacyApiScannerTest extends TestCase
{
    private const string CLASS_CODE = <<<'PHP'
        <?php
        class UserEntity
        {
            public function __construct(array $data) { parent::__construct($data); }
            public function raw(): array { return $this->data; }
            public function copy(): array { return $this->getArrayCopy(); }
            public function nextVersion($v) { return $v + 1; }
            public function name(): string { return $this->name . $other->data . $this->format(); }
            abstract public function declared(): void;
            public function dynamic(): mixed { return $this->{$field} ?? self::make(); }
        }
        PHP;

    #[Test]
    public function notesMethodsUsingTheLegacyApi(): void
    {
        $class = (new NodeFinder())->findFirstInstanceOf(
            (new ParserFactory())->createForHostVersion()
                ->parse(self::CLASS_CODE) ?? [],
            Class_::class,
        );

        static::assertSame(
            [
                'method __construct() uses the 1.x entity API; rewrite it for typed properties',
                'method raw() uses the 1.x entity API; rewrite it for typed properties',
                'method copy() uses the 1.x entity API; rewrite it for typed properties',
                'method nextVersion() uses the 1.x entity API; rewrite it for typed properties',
            ],
            null === $class ? [] : LegacyApiScanner::scan($class),
        );
    }
}
