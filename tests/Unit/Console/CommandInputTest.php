<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\Unit\Console;

use Contenir\Db\Model\Tools\Console\CommandInput;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;

#[CoversClass(CommandInput::class)]
#[Group('unit')]
final class CommandInputTest extends TestCase
{
    /**
     * @param array<string, mixed> $parameters
     */
    private static function input(array $parameters): CommandInput
    {
        return new CommandInput(new ArrayInput($parameters, new InputDefinition([
            new InputArgument('tables', InputArgument::IS_ARRAY),
            new InputOption('name', null, InputOption::VALUE_REQUIRED),
            new InputOption('force', null, InputOption::VALUE_NONE),
        ])));
    }

    #[Test]
    public function emptyArgumentsAreDropped(): void
    {
        static::assertSame(['users'], self::input(['tables' => ['', 'users']])->arguments('tables'));
    }

    #[Test]
    public function missingValuesAreEmpty(): void
    {
        $input = self::input([]);

        static::assertSame([[], null, false], [
            $input->arguments('tables'),
            $input->option('name'),
            $input->flag('force'),
        ]);
    }

    #[Test]
    public function readsGivenValues(): void
    {
        $input = self::input(['tables' => ['users', 'orders'], '--name' => 'x', '--force' => true]);

        static::assertSame([['users', 'orders'], 'x', true], [
            $input->arguments('tables'),
            $input->option('name'),
            $input->flag('force'),
        ]);
    }
}
