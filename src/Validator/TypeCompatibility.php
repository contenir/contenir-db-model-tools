<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

use BackedEnum;
use Contenir\Db\Model\Metadata\FieldType;
use Contenir\Db\Model\Value\SensitiveString;
use DateTimeImmutable;
use DateTimeInterface;
use ReflectionEnum;

use function in_array;
use function is_a;
use function is_subclass_of;

/**
 * Whether a property's declared type can hold a column's values. String
 * properties, union types and unknown classes (custom converters) are
 * always accepted.
 *
 * @internal
 */
final readonly class TypeCompatibility
{
    private const array ACCEPTS = [
        'int'                    => ['int', 'bool'],
        'bool'                   => ['bool', 'int'],
        'float'                  => ['float', 'int', 'string'],
        'array'                  => ['array', 'string'],
        DateTimeImmutable::class => [DateTimeImmutable::class, 'string'],
    ];

    /**
     * @param string $columnType the PHP type the generator would use for the column
     */
    public static function accepts(FieldType $field, string $columnType): bool
    {
        $accepted = self::ACCEPTS[self::normalise($field->phpType)] ?? null;

        return null === $accepted || in_array($columnType, $accepted, strict: true);
    }

    /**
     * The scalar or date type a property type stores as.
     *
     * @mago-expect analysis:unhandled-thrown-type ReflectionEnum cannot throw for a class already known to be an enum.
     */
    private static function normalise(?string $phpType): string
    {
        if (null === $phpType || SensitiveString::class === $phpType) {
            return 'string';
        }

        if (is_a($phpType, DateTimeInterface::class, allow_string: true)) {
            return DateTimeImmutable::class;
        }

        if (is_subclass_of($phpType, BackedEnum::class)) {
            return (string) (new ReflectionEnum($phpType))->getBackingType();
        }

        return $phpType;
    }
}
