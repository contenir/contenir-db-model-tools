<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Console;

use Contenir\Db\Model\Tools\Connection\AdapterFactory;
use Contenir\Db\Model\Tools\Exception\ToolException;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;

/**
 * The adapter a command reads from: --dsn when given, otherwise the one
 * injected from the application's container.
 *
 * @internal
 */
final readonly class Connection
{
    /**
     * @throws ToolException
     */
    public static function resolve(
        CommandInput $input,
        (AdapterInterface&SchemaAwareInterface)|null $injected,
    ): AdapterInterface&SchemaAwareInterface {
        $dsn = $input->option('dsn');
        if (null !== $dsn) {
            return AdapterFactory::fromDsn($dsn);
        }

        return $injected ?? throw ToolException::noConnection();
    }
}
