<?php

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Utils;

use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\OperationInterface;

final class HttpBindingOperationFixture implements OperationInterface
{
    public const NAME = 'GetThing';
    public const ID = 'smithy.test#GetThing';
    public const INPUT_CLASS = HttpBindingInputShapeFixture::class;
    public const OUTPUT_CLASS = HttpBindingOutputShapeFixture::class;
    public const ERRORS = [];

    private static ?self $INSTANCE = null;

    /**
     * Get the shared operation instance.
     */
    public static function instance(): self
    {
        return self::$INSTANCE ??= new self();
    }

    private function __construct() {}

    /**
     * Get the operation name.
     */
    public function getName(): string
    {
        return self::NAME;
    }

    /**
     * Get the operation shape ID.
     */
    public function getId(): string
    {
        return self::ID;
    }

    /**
     * Return the runtime identity.
     */
    public function getRuntimeIdentity(): string
    {
        return self::class;
    }

    /**
     * Return effective authentication schemes.
     *
     * @return list<string>
     */
    public function getEffectiveAuthSchemes(): array
    {
        return [];
    }

    /**
     * Get the operation input class.
     */
    public function getInputClass(): string
    {
        return self::INPUT_CLASS;
    }

    /**
     * Get the operation output class.
     */
    public function getOutputClass(): string
    {
        return self::OUTPUT_CLASS;
    }

    /**
     * Get the modeled operation error classes.
     */
    public function getErrors(): array
    {
        return self::ERRORS;
    }

    /**
     * Materialize an input value.
     *
     * @param array<int, mixed> $members Positional member values.
     */
    public function createInput(
        array $members
    ): \Smithy\Core\Serde\SerializableStructInterface
    {
        $inputClass = $this->getInputClass();

        return new $inputClass(...$members);
    }

    /**
     * Materialize an output value.
     *
     * @param array<int, mixed> $members Positional member values.
     */
    public function createOutput(
        array $members
    ): \Smithy\Core\Serde\SerializableStructInterface
    {
        $outputClass = $this->getOutputClass();

        return $outputClass::fromMembers($members);
    }

    /**
     * Get the operation schema.
     */
    public function getSchema(): Schema
    {
        return self::schema();
    }

    /**
     * Get the operation schema.
     */
    public static function schema(): Schema
    {
        return HttpBindingFixtureSchemas::httpOperation();
    }

    /**
     * Get the input shape schema.
     */
    public function getInputSchema(): Schema
    {
        return self::inputSchema();
    }

    /**
     * Get the input shape schema.
     */
    public static function inputSchema(): Schema
    {
        return HttpBindingInputShapeFixture::schema();
    }

    /**
     * Get the output shape schema.
     */
    public function getOutputSchema(): Schema
    {
        return self::outputSchema();
    }

    /**
     * Return modeled error schemas.
     *
     * @return list<Schema>
     */
    public function getErrorSchemas(): array
    {
        return [];
    }

    /**
     * Get the output shape schema.
     */
    public static function outputSchema(): Schema
    {
        return HttpBindingOutputShapeFixture::schema();
    }

    /**
     * Return the protocol request target.
     */
    public function getRequestTarget(): ?string
    {
        return \defined(static::class . '::TARGET')
            ? \constant(static::class . '::TARGET')
            : null;
    }

    /**
     * Resolve a modeled error schema.
     */
    public function resolveErrorSchema(string $discriminator): ?Schema
    {
        $errorMap = $this->getSchema()->getTrait(Schema::T_ERRORS) ?? [];
        $errorClass = $errorMap[$discriminator] ?? null;

        return \is_string($errorClass)
            && \method_exists($errorClass, 'schema')
                ? $errorClass::schema()
                : null;
    }

    /**
     * Get the modeled input stream member.
     */
    public function getInputStreamMember(): ?Schema
    {
        return null;
    }

    /**
     * Get the modeled output stream member.
     */
    public function getOutputStreamMember(): ?Schema
    {
        return null;
    }
}
