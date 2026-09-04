<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Dynamic\Utils;

use Smithy\Client\Dynamic\{
    DynamicClient,
    DynamicClientRuntime
};

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

        return $runtime ??= new DynamicClientRuntime(
            protocols: [
                'example.protocols#custom' =>
                    new IdentifiedProtocolFixture(
                        'example.protocols#custom'
                    )
            ]
        );
    }
}
