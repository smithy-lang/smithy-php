<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Client\Dynamic\{
    DynamicClient,
    DynamicClientRuntime
};
use Smithy\Core\Serde\SchemaTraitRegistry;
use Smithy\Tests\Client\Fake\TestAuthSchemeFixture;

/**
 * Test specialization with a custom default runtime.
 */
final class TestDynamicClient extends DynamicClient
{
    /**
     * @inheritDoc
     */
    protected static function getDefaultRuntime(): DynamicClientRuntime
    {
        static $runtime;

        if ($runtime !== null) {
            return $runtime;
        }

        $authScheme = new TestAuthSchemeFixture('example.auth#custom');
        $endpointFunction = new AlwaysTrueFunctionFixture();
        $traitRegistry = new SchemaTraitRegistry();
        $traitRegistry->register(
            'example#upper',
            materializer: static fn(mixed $value): string =>
                \strtoupper((string) $value)
        );

        return $runtime = parent::getDefaultRuntime()->merge(
            new DynamicClientRuntime(
                protocols: [
                    'example.protocols#custom' =>
                        new IdentifiedProtocolFixture(
                            'example.protocols#custom'
                        )
                ],
                authSchemes: [$authScheme],
                endpointFunctions: [
                    $endpointFunction->getName() => $endpointFunction
                ],
                endpointAuthSchemeIds: [
                    'custom' => $authScheme->getSchemeId()
                ],
                protocolPreference: ['example.protocols#custom'],
                traitRegistry: $traitRegistry
            )
        );
    }
}
