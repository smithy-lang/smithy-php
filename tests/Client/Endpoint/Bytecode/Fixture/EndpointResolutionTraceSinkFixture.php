<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Endpoint\Bytecode\Fixture;

use Smithy\Client\Endpoint\Bytecode\{
    Bytecode,
    EndpointResolutionTraceInterface,
    EndpointResolutionTraceSinkInterface
};

final class EndpointResolutionTraceSinkFixture implements
    EndpointResolutionTraceSinkInterface
{
    /** @var list<array<string, mixed>> */
    public array $parameters = [];

    /** @var list<EndpointResolutionTraceFixture> */
    public array $traces = [];

    public int $calls = 0;

    public function __construct(public bool $enabled = true) {}

    public function begin(
        Bytecode $bytecode,
        array $parameters
    ): ?EndpointResolutionTraceInterface
    {
        $this->calls++;
        $this->parameters[] = $parameters;
        if (!$this->enabled) {
            return null;
        }

        $trace = new EndpointResolutionTraceFixture();
        $this->traces[] = $trace;

        return $trace;
    }
}
