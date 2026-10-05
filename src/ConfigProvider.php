<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools;

use Contenir\Db\Model\Tools\Command\GenerateEntityCommand;
use Contenir\Db\Model\Tools\Command\UpgradeEntityCommand;
use Contenir\Db\Model\Tools\Command\ValidateMappingCommand;
use Contenir\Db\Model\Tools\Container\CommandFactory;

/**
 * Registers the commands with laminas-cli, as db-model:entity:generate,
 * db-model:entity:upgrade and db-model:mapping:validate.
 *
 * @api
 */
final readonly class ConfigProvider
{
    /**
     * @return array<string, class-string>
     */
    public function getCommands(): array
    {
        return [
            'db-model:entity:generate'  => GenerateEntityCommand::class,
            'db-model:entity:upgrade'   => UpgradeEntityCommand::class,
            'db-model:mapping:validate' => ValidateMappingCommand::class,
        ];
    }

    /**
     * @return array{factories: array<class-string, class-string>}
     */
    public function getDependencies(): array
    {
        return [
            'factories' => [
                GenerateEntityCommand::class  => CommandFactory::class,
                UpgradeEntityCommand::class   => CommandFactory::class,
                ValidateMappingCommand::class => CommandFactory::class,
            ],
        ];
    }

    /**
     * @return array{
     *     dependencies: array{factories: array<class-string, class-string>},
     *     laminas-cli: array{commands: array<string, class-string>},
     * }
     */
    public function __invoke(): array
    {
        return [
            'dependencies' => $this->getDependencies(),
            'laminas-cli'  => ['commands' => $this->getCommands()],
        ];
    }
}
