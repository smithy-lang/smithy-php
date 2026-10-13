<?php

declare(strict_types=1);

namespace Smithy\Examples\PetStore\Example;

use Smithy\Client\ClientConfig;
use Smithy\Client\Endpoint\{
    Endpoint,
    StaticEndpointResolver
};
use Smithy\Examples\PetStore\PetStoreClient;
use Smithy\Http\Client\{
    CurlClient,
    HttpClientTransport
};
use Smithy\Protocol\RpcV2\RpcV2CborProtocol;

/**
 * Build a PetStore client configured for the local example server.
 */
final class ClientFactory
{
    private function __construct() {}

    public static function create(?string $endpoint = null): PetStoreClient
    {
        $endpoint ??= \getenv('PET_STORE_ENDPOINT') ?: null;
        $endpoint ??= 'http://127.0.0.1:8080';

        return new PetStoreClient(new ClientConfig(
            protocol: new RpcV2CborProtocol(),
            transport: new HttpClientTransport(
                new CurlClient()
            ),
            endpointResolver: new StaticEndpointResolver(
                new Endpoint($endpoint)
            )
        ));
    }
}
