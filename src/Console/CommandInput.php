<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Console;

use Symfony\Component\Console\Input\InputInterface;

use function array_values;
use function is_array;
use function is_string;

/**
 * Typed access to Symfony console input, whose getters return mixed.
 *
 * @internal
 */
final readonly class CommandInput
{
    public function __construct(
        private InputInterface $input,
    ) {}

    /**
     * @return list<string>
     *
     * @mago-expect analysis:mixed-assignment Console input is untyped; validated here.
     */
    private static function strings(mixed $value): array
    {
        $list = [];
        foreach (is_array($value) ? array_values($value) : [$value] as $item) {
            if (! is_string($item) || '' === $item) {
                continue;
            }

            $list[] = $item;
        }

        return $list;
    }

    /**
     * Values of an array argument, with empty values dropped.
     *
     * @return list<string>
     */
    public function arguments(string $name): array
    {
        return self::strings($this->input->getArgument($name));
    }

    public function flag(string $name): bool
    {
        return true === $this->input->getOption($name);
    }

    /**
     * @mago-expect analysis:mixed-assignment Console input is untyped; validated here.
     */
    public function option(string $name): ?string
    {
        $value = $this->input->getOption($name);

        return is_string($value) && '' !== $value ? $value : null;
    }

    /**
     * Values of an array option, with empty values dropped.
     *
     * @return list<string>
     */
    public function options(string $name): array
    {
        return self::strings($this->input->getOption($name));
    }
}
