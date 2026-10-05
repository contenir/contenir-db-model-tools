<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

use function count;

/**
 * Issues found per entity class.
 *
 * @api
 */
final class ValidationReport
{
    /**
     * @var array<string, list<Issue>>
     */
    private array $issues = [];

    /**
     * @param list<Issue> $issues
     */
    public function add(string $className, array $issues): void
    {
        $this->issues[$className] = $issues;
    }

    public function count(Severity $severity): int
    {
        $count = 0;
        foreach ($this->issues as $issues) {
            foreach ($issues as $issue) {
                $count += (int) ($severity === $issue->severity);
            }
        }

        return $count;
    }

    /**
     * @return array<string, list<Issue>> keyed by class name, in validation order
     */
    public function entities(): array
    {
        return $this->issues;
    }

    public function passes(bool $strict = false): bool
    {
        return 0 === $this->count(Severity::Error) && (! $strict || 0 === $this->count(Severity::Warning));
    }

    public function size(): int
    {
        return count($this->issues);
    }
}
