<?php

declare(strict_types=1);

namespace ContenirTest\Db\Model\Tools\TestAsset;

use Contenir\Db\Model\Metadata\AttributeMetadataFactory;
use Contenir\Db\Model\Metadata\EntityMetadata;
use Contenir\Db\Model\Tools\Generator\GeneratedClass;

use function bin2hex;
use function random_bytes;
use function str_replace;
use function substr;

/**
 * Loads generated source into a unique namespace (so tests never collide)
 * and returns its contenir-db-model metadata, proving the mapping is valid.
 */
final class GeneratedClassLoader
{
    /**
     * @param list<GeneratedClass> $classes generated together (relations may reference each other)
     * @param string               $namespace the namespace they were generated into
     *
     * @return array<string, EntityMetadata<object>> keyed by short class name
     *
     * @mago-expect lint:no-eval Generated test code is loaded in-process to validate its mapping.
     */
    public static function load(array $classes, string $namespace): array
    {
        $unique = "{$namespace}\\T" . bin2hex(random_bytes(6));
        foreach ($classes as $class) {
            eval(substr(str_replace("namespace {$namespace};", "namespace {$unique};", $class->source), offset: 5));
        }

        $factory  = new AttributeMetadataFactory();
        $metadata = [];
        foreach ($classes as $class) {
            /** @var class-string $name */
            $name                        = "{$unique}\\{$class->className}";
            $metadata[$class->className] = $factory->getMetadataFor($name);
        }

        return $metadata;
    }
}
