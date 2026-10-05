<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Validator;

use Contenir\Db\Model\Metadata\FieldType;
use Contenir\Db\Model\Tools\Validator\TypeCompatibility;
use Contenir\Db\Model\Value\SensitiveString;
use ContenirTest\Db\Model\Tools\TestAsset\Enum\Priority;
use ContenirTest\Db\Model\Tools\TestAsset\Enum\Status;
use DateTime;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use stdClass;

#[CoversClass(TypeCompatibility::class)]
#[Group('unit')]
final class TypeCompatibilityTest extends TestCase
{
    /**
     * @return array<string, array{string|null, string, bool}>
     */
    public static function typeProvider(): array
    {
        return [
            'int on int'               => ['int', 'int', true],
            'int on bool'              => ['int', 'bool', true],
            'int on string'            => ['int', 'string', false],
            'bool on int'              => ['bool', 'int', true],
            'bool on date'             => ['bool', DateTimeImmutable::class, false],
            'float on decimal'         => ['float', 'string', true],
            'float on array'           => ['float', 'array', false],
            'array on text'            => ['array', 'string', true],
            'array on int'             => ['array', 'int', false],
            'string on anything'       => ['string', 'int', true],
            'union type'               => [null, 'int', true],
            'sensitive string'         => [SensitiveString::class, 'int', true],
            'mutable date on datetime' => [DateTime::class, DateTimeImmutable::class, true],
            'date on int'              => [DateTimeImmutable::class, 'int', false],
            'string enum on string'    => [Status::class, 'string', true],
            'int enum on int'          => [Priority::class, 'int', true],
            'int enum on string'       => [Priority::class, 'string', false],
            'unknown class'            => [stdClass::class, 'int', true],
        ];
    }

    #[DataProvider('typeProvider')]
    #[Test]
    public function decidesWhetherAPropertyTypeCanHoldColumnValues(
        ?string $phpType,
        string $columnType,
        bool $accepted,
    ): void {
        static::assertSame($accepted, TypeCompatibility::accepts(new FieldType($phpType, false), $columnType));
    }
}
