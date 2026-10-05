<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Upgrade;

use Contenir\Db\Model\Tools\Exception\UpgradeException;

use function is_array;
use function is_string;

/**
 * Reads a 1.x entity's $relations array into {@see LegacyRelation}s.
 *
 * @internal
 */
final readonly class RelationConfigReader
{
    public function __construct(
        private TargetResolver $targets,
    ) {}

    /**
     * @param array<array-key, mixed> $relations
     * @param list<string>            $notes     receives notes on settings that were dropped
     *
     * @return list<LegacyRelation>
     *
     * @throws UpgradeException When a relation lacks its columns or target.
     *
     * @mago-expect analysis:mixed-assignment 1.x configuration is untyped; each entry is checked.
     */
    public function read(string $class, array $relations, array &$notes): array
    {
        $result = [];
        foreach ($relations as $name => $entry) {
            $name   = (string) $name;
            $config = is_array($entry) ? $entry : [];
            $target = is_array($config['table'] ?? null) && is_string($config['table']['class'] ?? null)
                ? $config['table']['class']
                : null;
            $keys = LegacyKeys::read($config);
            if (null === $target || null === $keys) {
                throw UpgradeException::invalidRelation($class, $name, '"column" and "table.class" are required');
            }

            $result[] = new LegacyRelation(
                $name,
                'single' === ($config['type'] ?? 'many'),
                new LegacyTarget($this->targets->resolve($target), $target),
                $keys,
                LegacyCriteria::read($name, $config, $notes),
            );
        }

        return $result;
    }
}
