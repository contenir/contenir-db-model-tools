<?php

declare(strict_types=1);

namespace Contenir\Db\Model\Tools\Validator;

use Contenir\Db\Model\Exception\MappingException;
use Contenir\Db\Model\Metadata\MetadataFactoryInterface;
use Contenir\Db\Model\Tools\Exception\ToolException;
use Contenir\Db\Model\Tools\Schema\SchemaReader;

use function class_exists;
use function in_array;
use function sprintf;

/**
 * Validates an entity class's mapping against the live database schema.
 *
 * @api
 */
final readonly class SchemaValidator
{
    public function __construct(
        private SchemaReader $reader,
        private MetadataFactoryInterface $metadata,
    ) {}

    /**
     * @return list<Issue> empty when the mapping matches the schema
     *
     * @throws ToolException When the schema cannot be read.
     */
    public function validate(string $className): array
    {
        if (! class_exists($className)) {
            return [Issue::error(sprintf('Class %s cannot be autoloaded', $className))];
        }

        try {
            $metadata = $this->metadata->getMetadataFor($className);
        } catch (MappingException $e) {
            return [Issue::error($e->getMessage())];
        }

        if (! in_array($metadata->table, $this->reader->tableNames($metadata->schema), strict: true)) {
            return [Issue::error(sprintf('Table "%s" does not exist', $metadata->table))];
        }

        $table = $this->reader->read($metadata->table, $metadata->schema);

        return [
            ...KeyChecks::check($metadata, $table),
            ...ColumnChecks::check($metadata, $table),
            ...RelationChecks::check($metadata, $this->reader),
        ];
    }
}
