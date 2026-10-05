<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Discovery;

use Contenir\Db\Model\Tools\Exception\ToolException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;
use UnexpectedValueException;

use function sort;

/**
 * Lists the .php files in a directory tree.
 *
 * @internal
 */
final readonly class PhpFiles
{
    /**
     * @return list<string> sorted paths
     *
     * @throws ToolException When the directory cannot be read.
     */
    public static function in(string $directory): array
    {
        try {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(
                $directory,
                FilesystemIterator::SKIP_DOTS,
            ));
        } catch (UnexpectedValueException $e) {
            throw ToolException::cannotRead($directory, $e->getMessage());
        }

        $files = [];
        foreach ($iterator as $file) {
            if (! $file instanceof SplFileInfo || 'php' !== $file->getExtension()) {
                continue;
            }

            $files[] = $file->getPathname();
        }

        sort($files);

        return $files;
    }
}
