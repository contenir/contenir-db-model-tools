<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

/**
 * One difference between an entity's mapping and the live schema.
 *
 * @api
 */
final readonly class Issue
{
    public function __construct(
        public Severity $severity,
        public string $message,
    ) {}

    public static function error(string $message): self
    {
        return new self(Severity::Error, $message);
    }

    public static function warning(string $message): self
    {
        return new self(Severity::Warning, $message);
    }
}
