<?php

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Model\SmithyModel;
use Smithy\Codegen\ServiceProjection;

final class ServiceProjectionTest extends TestCase
{
    public function testCompleteProjectionIncludesEveryBoundOperation(): void
    {
        $projection = ServiceProjection::fromModel($this->model(), null);

        $this->assertFalse($projection->trimmed);
        $this->assertSame(
            [
                'smithy.example#GetPet',
                'smithy.example#ListPets',
                'smithy.example#DeletePet'
            ],
            $projection->operationIds
        );
        $this->assertArrayHasKey(
            'smithy.example#DeleteOnlyError',
            $projection->shapeIds
        );
        $this->assertArrayHasKey(
            'smithy.example#ServiceError',
            $projection->shapeIds
        );
        $this->assertArrayNotHasKey(
            'smithy.example#UnusedShape',
            $projection->shapeIds
        );
        $this->assertTrue($projection->hasErrors);
        $this->assertTrue($projection->usesUnit);
    }

    public function testTrimmedProjectionResolvesNamesAndShapeIds(): void
    {
        $projection = ServiceProjection::fromModel(
            $this->model(),
            [
                'ListPets',
                'smithy.example#GetPet'
            ]
        );

        $this->assertTrue($projection->trimmed);
        $this->assertSame(
            [
                'smithy.example#GetPet',
                'smithy.example#ListPets'
            ],
            $projection->operationIds
        );
        $this->assertArrayHasKey(
            'smithy.example#PetDetails',
            $projection->shapeIds
        );
        $this->assertArrayHasKey(
            'smithy.example#UnitUnion',
            $projection->shapeIds
        );
        $this->assertArrayHasKey(
            'smithy.example#CommonError',
            $projection->shapeIds
        );
        $this->assertArrayHasKey(
            'smithy.example#ServiceError',
            $projection->shapeIds
        );
        $this->assertArrayNotHasKey(
            'smithy.example#DeletePet',
            $projection->shapeIds
        );
        $this->assertArrayNotHasKey(
            'smithy.example#DeleteOnlyError',
            $projection->shapeIds
        );
        $this->assertTrue($projection->hasErrors);
        $this->assertTrue($projection->usesUnit);
    }

    public function testResourceBoundOperationCanBeSelected(): void
    {
        $projection = ServiceProjection::fromModel(
            $this->model(),
            ['DeletePet']
        );

        $this->assertSame(
            ['smithy.example#DeletePet'],
            $projection->operationIds
        );
        $this->assertArrayHasKey(
            'smithy.example#DeletePetInput',
            $projection->shapeIds
        );
        $this->assertArrayNotHasKey(
            'smithy.example#Pet',
            $projection->shapeIds
        );
        $this->assertTrue($projection->usesUnit);
    }

    public function testRejectsUnknownOperation(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Operation PutPet is not bound to service smithy.example#PetStore'
        );

        ServiceProjection::fromModel($this->model(), ['PutPet']);
    }

    public function testRejectsOperationSelectedByNameAndShapeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'operationSet selects smithy.example#GetPet more than once'
        );

        ServiceProjection::fromModel(
            $this->model(),
            [
                'GetPet',
                'smithy.example#GetPet'
            ]
        );
    }

    public function testRejectsOperationSetWithoutService(): void
    {
        $model = SmithyModel::fromJson(\json_encode([
            'smithy' => '2.0',
            'shapes' => [
                'smithy.example#Value' => [
                    'type' => 'structure',
                    'members' => []
                ]
            ]
        ], JSON_THROW_ON_ERROR));

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'operationSet requires a model with a service shape'
        );

        ServiceProjection::fromModel($model, ['GetPet']);
    }

    private function model(): SmithyModel
    {
        return SmithyModel::fromFile(
            __DIR__ . '/../fixtures/trimmed-client-model.json'
        );
    }
}
