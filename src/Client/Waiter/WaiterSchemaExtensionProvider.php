<?php

declare(strict_types=1);

namespace Smithy\Client\Waiter;

use Smithy\Core\Serde\{
    Schema,
    SchemaExtensionKey,
    SchemaExtensionProviderInterface
};

/**
 * Provide modeled waiter definitions for operation schemas.
 *
 * @internal
 */
final class WaiterSchemaExtensionProvider implements
    SchemaExtensionProviderInterface
{
    public const TRAIT = Schema::T_WAITABLE;

    private static ?SchemaExtensionKey $key = null;

    /**
     * Get the singleton waiter extension key.
     */
    public static function key(): SchemaExtensionKey
    {
        return self::$key ??= new SchemaExtensionKey(new self());
    }

    /**
     * Materialize and validate waiter metadata from an operation schema.
     */
    public function provide(Schema $schema): ?WaiterSchemaExtension
    {
        if (!$schema->hasTrait(self::TRAIT)) {
            return null;
        }

        $normalized = WaiterDefinitionNormalizer::normalize(
            $schema->id,
            $schema->getTrait(self::TRAIT)
        );
        $definitions = [];
        foreach ($normalized as $name => $definition) {
            $definitions[$name] = WaiterDefinition::fromArray(
                $name,
                $definition
            );
        }

        return new WaiterSchemaExtension($definitions);
    }
}
