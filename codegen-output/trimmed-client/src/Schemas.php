<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\CodegenOutput\TrimmedPetStore;

use Smithy\CodegenOutput\TrimmedPetStore\Exception\CommonErrorException;
use Smithy\CodegenOutput\TrimmedPetStore\Exception\ServiceErrorException;
use Smithy\CodegenOutput\TrimmedPetStore\Input\GetPetInput;
use Smithy\CodegenOutput\TrimmedPetStore\Output\GetPetOutput;
use Smithy\CodegenOutput\TrimmedPetStore\Shape\Pet;
use Smithy\CodegenOutput\TrimmedPetStore\Shape\PetDetails;
use Smithy\CodegenOutput\TrimmedPetStore\Shape\Unit;
use Smithy\CodegenOutput\TrimmedPetStore\Shape\UnitUnion;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

/**
 * Centralized schema registry for all shapes in this service.
 *
 * Stores Schema objects as lazy public static properties.
 * Each structure has a dedicated member initializer.
 */
final class Schemas
{
    public static Schema $GetPetInput;
    public static Schema $GetPetOutput;
    public static Schema $Pet;
    public static Schema $PetDetails;
    public static Schema $CommonErrorException;
    public static Schema $ServiceErrorException;
    public static Schema $UnitUnion;

    public static function getPetInput(): Schema
    {
        if (isset(self::$GetPetInput)) {
            return self::$GetPetInput;
        }

        return self::$GetPetInput = new Schema(
            id: 'smithy.example#GetPetInput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'petId', memberIndex: 0)
            ],
            targetClass: GetPetInput::class
        );
    }

    public static function getPetOutput(): Schema
    {
        if (isset(self::$GetPetOutput)) {
            return self::$GetPetOutput;
        }

        return self::$GetPetOutput = new Schema(
            id: 'smithy.example#GetPetOutput',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.example#Pet', type: ShapeType::Structure, memberName: 'pet', memberIndex: 0, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'name', memberIndex: 0), new Schema(id: 'smithy.example#PetDetails', type: ShapeType::Structure, memberName: 'details', memberIndex: 1, targetClass: PetDetails::class)], targetClass: Pet::class),
                new Schema(id: 'smithy.example#UnitUnion', type: ShapeType::Union, memberName: 'marker', memberIndex: 1, members: [new Schema(id: 'smithy.api#Unit', type: ShapeType::Structure, memberName: 'none', memberIndex: 0, targetClass: Unit::class)], targetClass: UnitUnion::class)
            ],
            targetClass: GetPetOutput::class
        );
    }

    public static function pet(): Schema
    {
        if (isset(self::$Pet)) {
            return self::$Pet;
        }

        return self::$Pet = new Schema(
            id: 'smithy.example#Pet',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'name', memberIndex: 0),
                new Schema(id: 'smithy.example#PetDetails', type: ShapeType::Structure, memberName: 'details', memberIndex: 1, members: [new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'breed', memberIndex: 0)], targetClass: PetDetails::class)
            ],
            targetClass: Pet::class
        );
    }

    public static function petDetails(): Schema
    {
        if (isset(self::$PetDetails)) {
            return self::$PetDetails;
        }

        return self::$PetDetails = new Schema(
            id: 'smithy.example#PetDetails',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'breed', memberIndex: 0)
            ],
            targetClass: PetDetails::class
        );
    }

    public static function commonErrorException(): Schema
    {
        if (isset(self::$CommonErrorException)) {
            return self::$CommonErrorException;
        }

        return self::$CommonErrorException = new Schema(
            id: 'smithy.example#CommonError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'message', memberIndex: 0)
            ], traits: [23 => 'client'],
            targetClass: CommonErrorException::class
        );
    }

    public static function serviceErrorException(): Schema
    {
        if (isset(self::$ServiceErrorException)) {
            return self::$ServiceErrorException;
        }

        return self::$ServiceErrorException = new Schema(
            id: 'smithy.example#ServiceError',
            type: ShapeType::Structure,
            members: [
                new Schema(id: 'smithy.api#String', type: ShapeType::String_, memberName: 'message', memberIndex: 0)
            ], traits: [23 => 'server'],
            targetClass: ServiceErrorException::class
        );
    }

    public static function unitUnion(): Schema
    {
        if (isset(self::$UnitUnion)) {
            return self::$UnitUnion;
        }

        return self::$UnitUnion = new Schema(
            id: 'smithy.example#UnitUnion',
            type: ShapeType::Union,
            members: [
                new Schema(id: 'smithy.api#Unit', type: ShapeType::Structure, memberName: 'none', memberIndex: 0, targetClass: Unit::class)
            ],
            targetClass: UnitUnion::class
        );
    }

}
