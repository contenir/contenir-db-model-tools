<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Exception;

use Contenir\Db\Model\Tools\Exception\ToolException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

#[CoversClass(ToolException::class)]
#[Group('unit')]
final class ToolExceptionTest extends TestCase
{
    #[Test]
    public function missingPlatformPackageSaysWhatToInstall(): void
    {
        static::assertSame(
            'Reading MySQL schemas needs php-db/phpdb-mysql; composer require --dev php-db/phpdb-mysql',
            ToolException::missingPlatformPackage('MySQL', 'php-db/phpdb-mysql')->getMessage(),
        );
    }
}
