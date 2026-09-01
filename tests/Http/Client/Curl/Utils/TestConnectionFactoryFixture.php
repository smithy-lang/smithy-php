<?php

declare(strict_types=1);

namespace Smithy\Tests\Http\Client\Curl\Utils;

use Smithy\Http\Client\Configuration\ConnectConfiguration;
use Smithy\Http\Client\Connection\{
    ConnectionFactoryInterface,
    HttpConnectionInterface,
    RequestContext
};

final class TestConnectionFactoryFixture implements ConnectionFactoryInterface
{
    public function __construct(
        private readonly \Closure $factory
    ) {}

    public function create(
        RequestContext $requestContext,
        ConnectConfiguration $config
    ): HttpConnectionInterface
    {
        return ($this->factory)(
            $requestContext,
            $config
        );
    }
}
