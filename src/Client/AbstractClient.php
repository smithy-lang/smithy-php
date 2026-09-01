<?php

declare(strict_types=1);

namespace Smithy\Client;

use Smithy\Client\Plugin\{
    IdempotencyTokenPlugin,
    PluginCollection
};
use Smithy\Core\Serde\SerializableStructInterface;
use Smithy\Core\Shape\OperationInterface;

/**
 * Provide shared execution behavior for generated service clients.
 *
 * Holds an immutable {@see ClientConfig} and a {@see ClientPipeline}.
 * Generated operation methods call {@see executeOperation()} with a static
 * operation singleton and a typed input shape. Each generated signature
 * narrows the return type to the operation's output shape.
 *
 * The AWS SDK layer subclasses this as `AbstractAwsClient` to layer
 * credential resolution and region configuration on top.
 */
abstract class AbstractClient implements ClientInterface
{
    /** Client configuration after plugin application. */
    protected readonly ClientConfig $config;

    /** Constructed once per client and reused across operations. */
    private readonly ClientPipeline $pipeline;

    /**
     * Apply plugins and add shared idempotency-token handling.
     */
    public function __construct(
        ClientConfig $config,
        PluginCollection $plugins = new PluginCollection()
    )
    {
        $plugins = $plugins->prepend(IdempotencyTokenPlugin::instance());
        $this->config = $plugins->apply($config);
        $this->pipeline = new ClientPipeline($this->config);
    }

    /**
     * Access the client configuration.
     */
    public function getConfig(): ClientConfig
    {
        return $this->config;
    }

    /**
     * Run an operation through the pipeline.
     *
     * Generated methods delegate here and narrow the return type through a
     * covariant signature. Direct callers use this method when the operation
     * is known only at runtime.
     *
     * ```php
     * public function putObject(
     *     PutObjectInput $input,
     *     ?RequestOverrideConfig $overrides = null
     * ): PutObjectOutput
     * {
     *     return $this->executeOperation(
     *         PutObjectOperation::instance(),
     *         $input,
     *         $overrides
     *     );
     * }
     * ```
     */
    public function executeOperation(
        OperationInterface $operation,
        SerializableStructInterface $input,
        ?RequestOverrideConfig $overrides = null
    ): SerializableStructInterface
    {
        return $this->pipeline->execute($operation, $input, $overrides);
    }
}
