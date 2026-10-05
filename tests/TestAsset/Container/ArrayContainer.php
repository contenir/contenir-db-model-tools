<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset\Container;

use Psr\Container\ContainerInterface;
use RuntimeException;

use function array_key_exists;

/**
 * A container over a fixed array of services.
 */
final readonly class ArrayContainer implements ContainerInterface
{
    /**
     * @param array<string, mixed> $services
     */
    public function __construct(
        private array $services,
    ) {}

    public function get(string $id): mixed
    {
        return array_key_exists($id, $this->services)
            ? $this->services[$id]
            : throw new RuntimeException("No service {$id}");
    }

    public function has(string $id): bool
    {
        return array_key_exists($id, $this->services);
    }
}
