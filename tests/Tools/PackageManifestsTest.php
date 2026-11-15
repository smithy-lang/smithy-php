<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Tools;

use PHPUnit\Framework\TestCase;
use Smithy\Tools\PackageManifests\PackageManifests;

require_once __DIR__ . '/../../tools/package-manifests/PackageManifests.php';

/**
 * Cover the comparison of package composer.json files with the root.
 */
final class PackageManifestsTest extends TestCase
{
    public function testAcceptsManifestsThatMatchTheRoot(): void
    {
        $root = [
            'require' => [
                'php' => '>=8.2',
                'psr/http-message' => '^2.0'
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^11.0'
            ],
            'replace' => [
                'smithy/core' => 'self.version',
                'smithy/http' => 'self.version'
            ]
        ];
        $packages = [
            'src/Core/composer.json' => [
                'name' => 'smithy/core',
                'require' => ['php' => '>=8.2']
            ],
            'src/Http/composer.json' => [
                'name' => 'smithy/http',
                'require' => [
                    'php' => '>=8.2',
                    'psr/http-message' => '^2.0',
                    'smithy/core' => 'self.version'
                ]
            ]
        ];

        self::assertSame([], PackageManifests::compare($root, $packages));
    }

    public function testReportsDifferencesInReplaceAndRequire(): void
    {
        $root = [
            'require' => [
                'php' => '>=8.2',
                'psr/http-message' => '^2.0',
                'vendor/unused' => '^1.0'
            ],
            'replace' => [
                'smithy/core' => 'self.version',
                'smithy/http' => 'self.version',
                'smithy/missing' => 'self.version'
            ]
        ];
        $packages = [
            'src/Core/composer.json' => [
                'name' => 'smithy/core',
                'require' => ['php' => '>=8.1']
            ],
            'src/Http/composer.json' => [
                'name' => 'smithy/http',
                'require' => [
                    'php' => '>=8.2',
                    'psr/http-message' => '^2.0',
                    'smithy/core' => '^1.0',
                    'vendor/extra' => '^1.0'
                ]
            ],
            'src/Extra/composer.json' => [
                'name' => 'smithy/extra',
                'require' => ['php' => '>=8.2']
            ]
        ];

        self::assertSame(
            [
                'src/Core/composer.json: require php is >=8.1,'
                    . ' root has >=8.2',
                'src/Http/composer.json: require smithy/core is ^1.0,'
                    . ' not self.version',
                'src/Http/composer.json: require vendor/extra is not in'
                    . ' root require',
                'src/Extra/composer.json: smithy/extra is not in root'
                    . ' replace as self.version',
                'composer.json: replace smithy/missing has no package'
                    . ' composer.json',
                'composer.json: require vendor/unused is in no package'
                    . ' composer.json'
            ],
            PackageManifests::compare($root, $packages)
        );
    }

    public function testReportsPackageWithoutPhpConstraint(): void
    {
        $root = [
            'require' => ['php' => '>=8.2'],
            'replace' => [
                'smithy/core' => 'self.version',
                'smithy/http' => 'self.version'
            ]
        ];
        $packages = [
            'src/Core/composer.json' => ['name' => 'smithy/core'],
            'src/Http/composer.json' => [
                'name' => 'smithy/http',
                'require' => [
                    'php' => '>=8.2',
                    'smithy/core' => 'self.version'
                ]
            ]
        ];

        self::assertSame(
            [
                'src/Core/composer.json: require php is missing,'
                    . ' root has >=8.2'
            ],
            PackageManifests::compare($root, $packages)
        );
    }

    public function testReportsManifestWithoutName(): void
    {
        $root = [
            'require' => ['php' => '>=8.2'],
            'replace' => ['smithy/core' => 'self.version']
        ];
        $packages = [
            'src/Core/composer.json' => ['require' => ['php' => '>=8.2']]
        ];

        self::assertSame(
            [
                'src/Core/composer.json: has no name',
                'composer.json: replace smithy/core has no package'
                    . ' composer.json'
            ],
            PackageManifests::compare($root, $packages)
        );
    }

    public function testReportsTwoManifestsWithTheSameName(): void
    {
        $root = [
            'require' => ['php' => '>=8.2'],
            'replace' => ['smithy/core' => 'self.version']
        ];
        $packages = [
            'src/Copy/composer.json' => [
                'name' => 'smithy/core',
                'require' => ['php' => '>=8.2']
            ],
            'src/Core/composer.json' => [
                'name' => 'smithy/core',
                'require' => ['php' => '>=8.2']
            ]
        ];

        self::assertSame(
            [
                'src/Core/composer.json: smithy/core is also the name of'
                    . ' src/Copy/composer.json'
            ],
            PackageManifests::compare($root, $packages)
        );
    }

    public function testReportsReplaceThatIsNotSelfVersion(): void
    {
        $root = [
            'require' => ['php' => '>=8.2'],
            'replace' => ['smithy/core' => '*']
        ];
        $packages = [
            'src/Core/composer.json' => [
                'name' => 'smithy/core',
                'require' => ['php' => '>=8.2']
            ]
        ];

        self::assertSame(
            [
                'src/Core/composer.json: smithy/core is not in root replace'
                    . ' as self.version'
            ],
            PackageManifests::compare($root, $packages)
        );
    }

    public function testAcceptsExtensionThatTheRootSuggests(): void
    {
        $root = [
            'require' => ['php' => '>=8.2'],
            'replace' => ['smithy/http' => 'self.version'],
            'suggest' => ['ext-curl' => 'Required by the cURL transport.']
        ];
        $packages = [
            'src/Http/composer.json' => [
                'name' => 'smithy/http',
                'require' => [
                    'ext-curl' => '*',
                    'php' => '>=8.2'
                ]
            ]
        ];

        self::assertSame([], PackageManifests::compare($root, $packages));
    }

    public function testReportsExtensionThatTheRootNamesNowhere(): void
    {
        $root = [
            'require' => ['php' => '>=8.2'],
            'replace' => ['smithy/http' => 'self.version'],
            'suggest' => ['ext-curl' => 'Required by the cURL transport.']
        ];
        $packages = [
            'src/Http/composer.json' => [
                'name' => 'smithy/http',
                'require' => [
                    'ext-zip' => '*',
                    'php' => '>=8.2'
                ]
            ]
        ];

        self::assertSame(
            [
                'src/Http/composer.json: require ext-zip is in neither root'
                    . ' require nor root suggest'
            ],
            PackageManifests::compare($root, $packages)
        );
    }

    public function testReportsExtensionConstraintThatDiffersFromTheRoot(): void
    {
        $root = [
            'require' => [
                'ext-curl' => '*',
                'php' => '>=8.2'
            ],
            'replace' => ['smithy/http' => 'self.version']
        ];
        $packages = [
            'src/Http/composer.json' => [
                'name' => 'smithy/http',
                'require' => [
                    'ext-curl' => '>=7.0',
                    'php' => '>=8.2'
                ]
            ]
        ];

        self::assertSame(
            [
                'src/Http/composer.json: require ext-curl is >=7.0,'
                    . ' root has *'
            ],
            PackageManifests::compare($root, $packages)
        );
    }
}
