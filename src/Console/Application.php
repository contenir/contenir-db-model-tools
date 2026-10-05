<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Console;

use Contenir\Db\Model\Tools\Command\GenerateEntityCommand;
use Symfony\Component\Console\Application as ConsoleApplication;

/**
 * The standalone "db-model" console, reading the database from --dsn.
 *
 * @api
 */
final class Application extends ConsoleApplication
{
    public function __construct()
    {
        parent::__construct('contenir-db-model-tools');

        $this->addCommands([
            new GenerateEntityCommand(),
        ]);
    }
}
