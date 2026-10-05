<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Command;

use Closure;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Generator\GeneratedClass;

use function file_exists;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function restore_error_handler;
use function rtrim;
use function set_error_handler;

/**
 * Writes generated classes to "<directory>/<ClassName>.php", refusing to
 * overwrite unless forced.
 *
 * @internal
 */
final readonly class EntityWriter
{
    public function __construct(
        private string $directory,
        private bool $force = false,
    ) {}

    /**
     * Run a file operation, turning failure (and PHP's warning) into a
     * ToolException carrying the reason.
     *
     * @param Closure(): bool $operation
     *
     * @throws ToolException
     *
     * @mago-expect analysis:unused-parameter set_error_handler() passes the error level first; only the message is used.
     */
    private static function attempt(string $path, Closure $operation): void
    {
        $reason = 'unknown error';
        set_error_handler(static function (int $level, string $message) use (&$reason): bool {
            $reason = $message;

            return true;
        });

        try {
            $succeeded = $operation();
        } finally {
            restore_error_handler();
        }

        if (! $succeeded) {
            throw ToolException::cannotWrite($path, $reason);
        }
    }

    /**
     * @return string the path written
     *
     * @throws ToolException When the file exists and overwriting was not forced, or cannot be written.
     */
    public function write(GeneratedClass $class): string
    {
        $path = rtrim($this->directory, characters: '/') . "/{$class->className}.php";
        if (file_exists($path) && ! $this->force) {
            throw ToolException::fileExists($path);
        }

        if (! is_dir($this->directory)) {
            self::attempt($this->directory, fn(): bool => mkdir($this->directory, permissions: 0o775, recursive: true));
        }

        self::attempt($path, static fn(): bool => false !== file_put_contents($path, $class->source));

        return $path;
    }
}
