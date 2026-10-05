<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools;

/**
 * laminas-mvc module: the {@see ConfigProvider} configuration, with its
 * dependencies under "service_manager".
 *
 * @api
 */
final readonly class Module
{
    /**
     * @return array{
     *     service_manager: array{factories: array<class-string, class-string>},
     *     laminas-cli: array{commands: array<string, class-string>},
     * }
     */
    public function getConfig(): array
    {
        $provider = new ConfigProvider();

        return [
            'service_manager' => $provider->getDependencies(),
            'laminas-cli'     => ['commands' => $provider->getCommands()],
        ];
    }
}
