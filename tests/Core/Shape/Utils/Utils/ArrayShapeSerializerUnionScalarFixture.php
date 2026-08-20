<?php

declare(strict_types=1);

namespace Smithy\Tests\Core\Shape\Utils\Utils;

final class ArrayShapeSerializerUnionScalarFixture
    extends ArrayShapeSerializerUnionFixture
{
    /**
     * Create a structure variant.
     */
    public function __construct(
        private readonly ArrayShapeSerializerScalarFixture $value
    ) {}

    /** @inheritDoc */
    public function tag(): string
    {
        return 'scalar';
    }

    /** @inheritDoc */
    public function value(): mixed
    {
        return $this->value;
    }
}
