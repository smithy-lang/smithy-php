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
     * Get the operation schema.
     */
    public static function schema(): Schema
    {
        return HttpBindingFixtureSchemas::httpOperation();
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
    public static function outputSchema(): Schema
    {
        return HttpBindingOutputShapeFixture::schema();
    }

    /**
     * Get the modeled input stream member.
     */
    public static function getInputStreamMember(): ?Schema
    {
        return null;
    }

    /**
     * Get the modeled output stream member.
     */
    public static function getOutputStreamMember(): ?Schema
    {
        return null;
    }
}
