<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen\Model;

use PHPUnit\Framework\TestCase;
use Smithy\Codegen\Model\SensitiveShapeDetector;
use Smithy\Codegen\Model\SmithyModel;

final class SensitiveShapeDetectorTest extends TestCase
{
    public function testDetectsDirectAndTransitiveSensitivity(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#Secret": {
            "type": "string",
            "traits": {"smithy.api#sensitive": {}}
        },
        "example#SecretList": {
            "type": "list",
            "member": {"target": "example#Secret"}
        },
        "example#SecretMap": {
            "type": "map",
            "key": {"target": "smithy.api#String"},
            "value": {"target": "example#SecretList"}
        },
        "example#Choice": {
            "type": "union",
            "members": {
                "secret": {"target": "example#SecretMap"},
                "visible": {"target": "smithy.api#String"}
            }
        },
        "example#Container": {
            "type": "structure",
            "members": {
                "choice": {"target": "example#Choice"}
            }
        },
        "example#DirectMember": {
            "type": "structure",
            "members": {
                "secret": {
                    "target": "smithy.api#String",
                    "traits": {"smithy.api#sensitive": {}}
                }
            }
        },
        "example#CycleA": {
            "type": "structure",
            "members": {
                "next": {"target": "example#CycleB"}
            }
        },
        "example#CycleB": {
            "type": "structure",
            "members": {
                "next": {"target": "example#CycleA"},
                "secret": {"target": "example#Secret"}
            }
        },
        "example#Safe": {
            "type": "structure",
            "members": {
                "value": {"target": "smithy.api#String"}
            }
        }
    }
}
JSON);
        $detector = new SensitiveShapeDetector($model);

        foreach ([
            'example#Secret',
            'example#SecretList',
            'example#SecretMap',
            'example#Choice',
            'example#Container',
            'example#DirectMember',
            'example#CycleA',
            'example#CycleB'
        ] as $shapeId) {
            $this->assertTrue(
                $detector->containsSensitiveData($shapeId),
                "{$shapeId} should contain sensitive data"
            );
        }

        $this->assertFalse(
            $detector->containsSensitiveData('example#Safe')
        );
    }

    public function testDetectsSensitivityInheritedFromMixin(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#SensitiveMixin": {
            "type": "structure",
            "traits": {
                "smithy.api#mixin": {},
                "smithy.api#sensitive": {}
            }
        },
        "example#Container": {
            "type": "structure",
            "mixins": [{"target": "example#SensitiveMixin"}]
        }
    }
}
JSON);
        $detector = new SensitiveShapeDetector($model);

        $this->assertTrue(
            $detector->containsSensitiveData('example#Container')
        );
    }

    public function testDoesNotInheritMixinLocalSensitivity(): void
    {
        $model = SmithyModel::fromJson(<<<'JSON'
{
    "smithy": "2.0",
    "shapes": {
        "example#SensitiveMixin": {
            "type": "structure",
            "traits": {
                "smithy.api#mixin": {
                    "localTraits": ["smithy.api#sensitive"]
                },
                "smithy.api#sensitive": {}
            }
        },
        "example#Container": {
            "type": "structure",
            "mixins": [{"target": "example#SensitiveMixin"}]
        }
    }
}
JSON);
        $detector = new SensitiveShapeDetector($model);

        $this->assertTrue(
            $detector->containsSensitiveData('example#SensitiveMixin')
        );
        $this->assertFalse(
            $detector->containsSensitiveData('example#Container')
        );
    }
}
