<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Console;

use Contenir\Db\Model\Tools\Discovery\PhpFiles;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Exception\UpgradeException;
use Contenir\Db\Model\Tools\Upgrade\UpgradeOptions;

use function array_push;
use function is_dir;
use function strpos;
use function substr;

/**
 * Reads entity:upgrade's files and options.
 *
 * @internal
 */
final readonly class UpgradeInput
{
    /**
     * Files named directly, plus the .php files under named directories.
     *
     * @return list<string>
     *
     * @throws ToolException
     */
    public static function files(CommandInput $input): array
    {
        $files = [];
        foreach ($input->arguments('paths') as $path) {
            if (is_dir($path)) {
                array_push($files, ...PhpFiles::in($path));

                continue;
            }

            $files[] = $path;
        }

        return $files;
    }

    /**
     * @throws UpgradeException When a --map pair is malformed.
     */
    public static function options(CommandInput $input): UpgradeOptions
    {
        $targets = [];
        foreach ($input->options('map') as $pair) {
            $at = strpos($pair, needle: '=');
            if (false === $at || 0 === $at) {
                throw UpgradeException::invalidMap($pair);
            }

            $targets[substr($pair, offset: 0, length: $at)] = substr($pair, offset: $at + 1);
        }

        return new UpgradeOptions(
            $input->option('table'),
            $input->option('schema'),
            ! $input->flag('camel-case'),
            $targets,
        );
    }
}
