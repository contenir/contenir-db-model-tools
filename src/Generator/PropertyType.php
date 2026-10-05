<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Generator;

/**
 * PHP type for a column, plus the converter name to put in
 * #[Column(type: ...)] when the declared type alone does not pick it.
 *
 * @api
 */
final readonly class PropertyType
{
    public function __construct(
        public string $phpType,
        public ?string $converter = null,
    ) {}
}
