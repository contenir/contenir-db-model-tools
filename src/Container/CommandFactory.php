<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Container;

use Contenir\Db\Model\Metadata\MetadataFactoryInterface;
use Contenir\Db\Model\Tools\Command\GenerateEntityCommand;
use Contenir\Db\Model\Tools\Command\UpgradeEntityCommand;
use Contenir\Db\Model\Tools\Command\ValidateMappingCommand;
use PhpDb\Adapter\AdapterInterface;
use PhpDb\Adapter\SchemaAwareInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;

use function is_array;
use function is_string;

/**
 * Builds the commands for laminas-cli with the application's database
 * adapter (the "contenir_db_model.adapter" service, as contenir-db-model
 * uses) and metadata factory, so --dsn becomes optional.
 *
 * @api
 */
final readonly class CommandFactory
{
    /**
     * @throws ContainerExceptionInterface
     *
     * @mago-expect analysis:mixed-assignment Container services and configuration are untyped; both are checked.
     */
    private static function adapter(ContainerInterface $container): (AdapterInterface&SchemaAwareInterface)|null
    {
        $config = $container->has('config') ? $container->get('config') : [];
        $module = is_array($config) && is_array($config['contenir_db_model'] ?? null)
            ? $config['contenir_db_model']
            : [];
        $name    = is_string($module['adapter'] ?? null) ? $module['adapter'] : AdapterInterface::class;
        $adapter = $container->has($name) ? $container->get($name) : null;

        return $adapter instanceof AdapterInterface && $adapter instanceof SchemaAwareInterface ? $adapter : null;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    private static function metadata(ContainerInterface $container): ?MetadataFactoryInterface
    {
        $metadata = $container->has(MetadataFactoryInterface::class)
            ? $container->get(MetadataFactoryInterface::class)
            : null;

        return $metadata instanceof MetadataFactoryInterface ? $metadata : null;
    }

    /**
     * @throws ContainerExceptionInterface
     */
    public function __invoke(ContainerInterface $container, string $requestedName): Command
    {
        $adapter = self::adapter($container);

        return match ($requestedName) {
            UpgradeEntityCommand::class   => new UpgradeEntityCommand($adapter),
            ValidateMappingCommand::class => new ValidateMappingCommand($adapter, self::metadata($container)),
            default                       => new GenerateEntityCommand($adapter),
        };
    }
}
