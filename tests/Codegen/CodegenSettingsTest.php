<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Codegen;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Codegen\CodegenSettings;
use Smithy\Codegen\ShapeCategory;

final class CodegenSettingsTest extends TestCase
{
    public function testCopiesWithCanonicalOverrides(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Original',
            outputDir: '/tmp/original',
            generateClient: true
        );

        $copy = $settings->copyWith([
            'namespace' => 'Smithy\\Integrated',
            'generateClient' => false
        ]);

        $this->assertSame('Smithy\\Original', $settings->namespace);
        $this->assertSame('Smithy\\Integrated', $copy->namespace);
        $this->assertFalse($copy->generateClient);
        $this->assertSame('/tmp/original', $copy->outputDir);
    }

    public function testConstructorAppliesDefaultsWhenOnlyRequiredArgsProvided(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame('Aws\\Example', $settings->namespace);
        $this->assertSame('/tmp/output', $settings->outputDir);
        $this->assertSame('categorized', $settings->organization);
        $this->assertSame(
            [
                'input' => 'Input',
                'output' => 'Output',
                'shapes' => 'Shape',
                'errors' => 'Exception',
                'operations' => 'Operation',
                'endpoint' => 'Endpoint'
            ],
            $settings->subNamespaces
        );
        $this->assertSame(
            ['enabled' => false, 'categories' => ['output']],
            $settings->arrayAccess
        );
        $this->assertSame(
            ['enabled' => false, 'categories' => ['all']],
            $settings->arraySerialization
        );
        $this->assertNull($settings->baseServiceException);
        $this->assertNull($settings->serviceExceptionClass);
        $this->assertSame([], $settings->rulesFunctions);
        $this->assertSame([], $settings->extendedOpcodes);
        $this->assertTrue($settings->preserveExistingMemberOrder);
        $this->assertNull($settings->operationSet);
        $this->assertSame([], $settings->waiterResourceInitialisms);
        $this->assertFalse($settings->assumeAsciiEndpointStrings);
        $this->assertSame(
            'Smithy\\Client\\ClientConfig',
            $settings->clientConfigClass
        );
        $this->assertSame(
            'Smithy\\Client\\ClientInterface',
            $settings->clientBaseInterface
        );
    }

    #[DataProvider('categoryNamespaceMatrix')]
    public function testResolveNamespaceRoutesCategorizedShapesIntoSubNamespaces(
        ShapeCategory $category,
        string $expected
    ): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame($expected, $settings->resolveNamespace($category));
    }

    /**
     * @return array<string, array{ShapeCategory, string}>
     */
    public static function categoryNamespaceMatrix(): array
    {
        return [
            'input' => [ShapeCategory::Input, 'Aws\\Example\\Input'],
            'output' => [ShapeCategory::Output, 'Aws\\Example\\Output'],
            'error' => [ShapeCategory::Error, 'Aws\\Example\\Exception'],
            'shape' => [ShapeCategory::Shape, 'Aws\\Example\\Shape']
        ];
    }

    public function testResolvesServiceExceptionClass(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame('Example', $settings->resolveServiceName());
        $this->assertSame(
            'Aws\\Example\\Exception\\ExampleException',
            $settings->resolveServiceExceptionClass()
        );

        $custom = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            serviceExceptionClass: 'Aws\\Example\\Exception\\SdkException'
        );
        $this->assertSame(
            'Aws\\Example\\Exception\\SdkException',
            $custom->resolveServiceExceptionClass()
        );
        $matchingParent = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            baseServiceException:
                'Aws\\Example\\Exception\\ExampleException'
        );

        $this->assertSame(
            'Aws\\Example\\Exception\\ExampleException',
            $matchingParent->baseServiceException
        );
    }

    public function testRejectsReservedServiceExceptionClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'serviceExceptionClass must not use a reserved PHP class name'
        );

        new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            serviceExceptionClass: 'class'
        );
    }

    public function testResolveNamespaceCollapsesAllCategoriesWhenFlatOrganization(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            organization: 'flat'
        );

        // Every category maps to the base namespace when the organization is
        // flat.
        $this->assertSame(
            'Aws\\Example',
            $settings->resolveNamespace(ShapeCategory::Input)
        );
        $this->assertSame(
            'Aws\\Example',
            $settings->resolveNamespace(ShapeCategory::Output)
        );
        $this->assertSame(
            'Aws\\Example',
            $settings->resolveNamespace(ShapeCategory::Error)
        );
        $this->assertSame(
            'Aws\\Example',
            $settings->resolveNamespace(ShapeCategory::Shape)
        );
    }

    public function testResolveNamespaceHonorsSubNamespaceOverride(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            subNamespaces: [
                'input' => 'Requests',
                'output' => 'Responses',
                'shapes' => 'Types',
                'errors' => 'Errors',
                'operations' => 'Ops',
                'endpoint' => 'Endpoints'
            ]
        );

        $this->assertSame(
            'Aws\\Example\\Requests',
            $settings->resolveNamespace(ShapeCategory::Input)
        );
        $this->assertSame(
            'Aws\\Example\\Responses',
            $settings->resolveNamespace(ShapeCategory::Output)
        );
        $this->assertSame(
            'Aws\\Example\\Errors',
            $settings->resolveNamespace(ShapeCategory::Error)
        );
        $this->assertSame(
            'Aws\\Example\\Types',
            $settings->resolveNamespace(ShapeCategory::Shape)
        );
    }

    #[DataProvider('categoryOutputDirMatrix')]
    public function testResolveOutputDirRoutesCategorizedShapesIntoSubDirs(
        ShapeCategory $category,
        string $expected
    ): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame($expected, $settings->resolveOutputDir($category));
    }

    /**
     * @return array<string, array{ShapeCategory, string}>
     */
    public static function categoryOutputDirMatrix(): array
    {
        $ds = DIRECTORY_SEPARATOR;
        return [
            'input' => [ShapeCategory::Input, '/tmp/output' . $ds . 'Input'],
            'output' => [ShapeCategory::Output, '/tmp/output' . $ds . 'Output'],
            'error' => [
                ShapeCategory::Error,
                '/tmp/output' . $ds . 'Exception'
            ],
            'shape' => [ShapeCategory::Shape, '/tmp/output' . $ds . 'Shape']
        ];
    }

    public function testResolveOutputDirReturnsBaseDirWhenFlatOrganization(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            organization: 'flat'
        );

        $this->assertSame(
            '/tmp/output',
            $settings->resolveOutputDir(ShapeCategory::Input)
        );
        $this->assertSame(
            '/tmp/output',
            $settings->resolveOutputDir(ShapeCategory::Output)
        );
    }

    public function testResolveOperationsNamespaceAppendsOperationsSubNamespace(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame(
            'Aws\\Example\\Operation',
            $settings->resolveOperationsNamespace()
        );
    }

    public function testResolveOperationsNamespaceReturnsBaseWhenFlat(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            organization: 'flat'
        );

        $this->assertSame(
            'Aws\\Example',
            $settings->resolveOperationsNamespace()
        );
    }

    public function testResolveOperationsOutputDirAppendsOperationsSubDir(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame(
            '/tmp/output' . DIRECTORY_SEPARATOR . 'Operation',
            $settings->resolveOperationsOutputDir()
        );
    }

    public function testResolveEndpointNamespaceAppendsEndpointSubNamespace(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame(
            'Aws\\Example\\Endpoint',
            $settings->resolveEndpointNamespace()
        );
    }

    public function testResolveEndpointNamespaceReturnsBaseWhenFlat(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            organization: 'flat'
        );

        $this->assertSame(
            'Aws\\Example',
            $settings->resolveEndpointNamespace()
        );
    }

    public function testResolveEndpointOutputDirAppendsEndpointSubDir(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output'
        );

        $this->assertSame(
            '/tmp/output' . DIRECTORY_SEPARATOR . 'Endpoint',
            $settings->resolveEndpointOutputDir()
        );
    }

    public function testShouldGenerateArrayAccessReturnsFalseWhenDisabled(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            arrayAccess: ['enabled' => false, 'categories' => ['output']]
        );

        $this->assertFalse(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Output)
        );
    }

    public function testShouldGenerateArrayAccessReturnsTrueOnlyForListedCategories(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            arrayAccess: ['enabled' => true, 'categories' => ['output']]
        );

        $this->assertTrue(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Output)
        );
        $this->assertFalse(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Input)
        );
        $this->assertFalse(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Shape)
        );
        $this->assertFalse(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Error)
        );
    }

    public function testShouldGenerateArrayAccessTrueForAllWhenAllListed(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            arrayAccess: ['enabled' => true, 'categories' => ['all']]
        );

        $this->assertTrue(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Input)
        );
        $this->assertTrue(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Output)
        );
        $this->assertTrue(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Error)
        );
        $this->assertTrue(
            $settings->shouldGenerateArrayAccess(ShapeCategory::Shape)
        );
    }

    public function testShouldGenerateArraySerializationReturnsFalseWhenDisabled(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            arraySerialization: [
                'enabled' => false,
                'categories' => ['all']
            ]
        );

        $this->assertFalse(
            $settings->shouldGenerateArraySerialization(
                ShapeCategory::Output
            )
        );
    }

    public function testShouldGenerateArraySerializationUsesConfiguredCategories(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            arraySerialization: [
                'enabled' => true,
                'categories' => ['output']
            ]
        );

        $this->assertTrue(
            $settings->shouldGenerateArraySerialization(
                ShapeCategory::Output
            )
        );
        $this->assertFalse(
            $settings->shouldGenerateArraySerialization(
                ShapeCategory::Input
            )
        );
    }

    public function testShouldGenerateArraySerializationSupportsAllCategories(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Aws\\Example',
            outputDir: '/tmp/output',
            arraySerialization: [
                'enabled' => true,
                'categories' => ['all']
            ]
        );

        foreach (ShapeCategory::cases() as $category) {
            $this->assertTrue(
                $settings->shouldGenerateArraySerialization($category)
            );
        }
    }

    public function testFromArrayBuildsSettingsFromConfigArray(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Aws\\Sns',
            'outputDir' => '/build/sns',
            'organization' => 'flat',
            'baseServiceException' => 'Aws\\Exception\\AwsException',
            'serviceExceptionClass' => 'Aws\\Sns\\Exception\\SnsException',
            'preserveExistingMemberOrder' => false,
            'operationSet' => ['Publish', 'ListTopics'],
            'waiterResourceInitialisms' => ['api' => 'API'],
            'assumeAsciiEndpointStrings' => true
        ]);

        $this->assertSame('Aws\\Sns', $settings->namespace);
        $this->assertSame('/build/sns', $settings->outputDir);
        $this->assertSame(
            ['Publish', 'ListTopics'],
            $settings->operationSet
        );
        $this->assertSame('flat', $settings->organization);
        $this->assertSame(
            'Aws\\Exception\\AwsException',
            $settings->baseServiceException
        );
        $this->assertSame(
            'Aws\\Sns\\Exception\\SnsException',
            $settings->serviceExceptionClass
        );
        $this->assertFalse($settings->preserveExistingMemberOrder);
        $this->assertSame(
            ['api' => 'API'],
            $settings->waiterResourceInitialisms
        );
        $this->assertTrue($settings->assumeAsciiEndpointStrings);
    }

    public function testFromArrayMergesOverriddenSubNamespacesWithDefaults(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Aws\\Sns',
            'outputDir' => '/build/sns',
            'subNamespaces' => ['input' => 'Requests']
        ]);

        // Only `input` overridden. the rest fall through to their defaults.
        $this->assertSame('Requests', $settings->subNamespaces['input']);
        $this->assertSame('Output', $settings->subNamespaces['output']);
        $this->assertSame('Shape', $settings->subNamespaces['shapes']);
        $this->assertSame('Exception', $settings->subNamespaces['errors']);
        $this->assertSame('Operation', $settings->subNamespaces['operations']);
        $this->assertSame('Endpoint', $settings->subNamespaces['endpoint']);
    }

    public function testFromArrayAcceptsSnakeCasePreserveOrderKey(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Aws\\Sns',
            'outputDir' => '/build/sns',
            'preserve_existing_member_order' => false
        ]);

        $this->assertFalse($settings->preserveExistingMemberOrder);
    }

    public function testFromArrayAcceptsClientConfigClassAliases(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Aws\\Sns',
            'outputDir' => '/build/sns',
            'client_config_class' => 'Vendor\\Runtime\\VendorClientConfig'
        ]);

        $this->assertSame(
            'Vendor\\Runtime\\VendorClientConfig',
            $settings->clientConfigClass
        );
    }

    public function testFromArrayAcceptsClientBaseInterfaceAliases(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Aws\\Sns',
            'outputDir' => '/build/sns',
            'client_base_interface' =>
                'Vendor\\Runtime\\VendorClientInterface'
        ]);

        $this->assertSame(
            'Vendor\\Runtime\\VendorClientInterface',
            $settings->clientBaseInterface
        );
        $this->assertSame(
            'Vendor\\Runtime\\VendorClientInterface',
            $settings->toArray()['clientBaseInterface']
        );
    }

    public function testFromArrayDefaultsPreserveOrderToTrueWhenAbsent(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Aws\\Sns',
            'outputDir' => '/build/sns'
        ]);

        $this->assertTrue($settings->preserveExistingMemberOrder);
    }

    public function testFromArrayRaisesWhenNamespaceMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('namespace is required');

        CodegenSettings::fromArray(['outputDir' => '/tmp/x']);
    }

    public function testFromArrayRaisesWhenOutputDirMissing(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('outputDir is required');

        CodegenSettings::fromArray(['namespace' => 'Aws\\X']);
    }

    public function testFromArrayRejectsNonArrayRulesFunctions(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('rulesFunctions must be an array');

        CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/x',
            'rulesFunctions' => 'aws.partition'
        ]);
    }

    public function testFromArrayRejectsInvalidRulesFunction(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'rulesFunctions must contain RulesFunctionInterface instances, '
                . 'got aws.partition'
        );

        CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/x',
            'rulesFunctions' => ['aws.partition' => 'not-a-function']
        ]);
    }

    public function testFromArrayRejectsUnknownSetting(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown codegen setting: unknown');

        CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/output',
            'unknown' => true
        ]);
    }

    public function testFromArrayRejectsBothAliasesForOneSetting(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Use either generateClient or generate_client, not both'
        );

        CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/output',
            'generateClient' => true,
            'generate_client' => false
        ]);
    }

    public function testFromArrayIgnoresNullAliasValues(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/output',
            'generateClient' => null,
            'generate_client' => false
        ]);

        $this->assertFalse($settings->generateClient);
    }

    public function testConstructorRejectsInvalidOrganization(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'organization must be "categorized" or "flat"'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            organization: 'grouped'
        );
    }

    public function testFromArrayReadsAuthSchemeIds(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/output',
            'authSchemeIds' => ['aws.auth#sigv4', 'aws.auth#sigv4a']
        ]);

        $this->assertSame(
            ['aws.auth#sigv4', 'aws.auth#sigv4a'],
            $settings->authSchemeIds
        );
        $this->assertSame(
            ['aws.auth#sigv4', 'aws.auth#sigv4a'],
            $settings->copyWith([])->authSchemeIds
        );
        $this->assertSame(
            [],
            (new CodegenSettings(
                namespace: 'Smithy\\Example',
                outputDir: '/tmp/output'
            ))->authSchemeIds
        );
    }

    public function testConstructorRejectsEmptyAuthSchemeId(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'authSchemeIds must be a list of shape IDs'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            authSchemeIds: ['aws.auth#sigv4', '']
        );
    }

    public function testConstructorRejectsEmptyOperationSet(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'operationSet must be a non-empty list'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            operationSet: []
        );
    }

    public function testConstructorRejectsInvalidOperationSetEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'operationSet must contain non-empty operation names or shape IDs'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            operationSet: ['GetThing', '']
        );
    }

    public function testConstructorRejectsDuplicateOperationSetEntry(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'operationSet contains duplicate entry GetThing'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            operationSet: ['GetThing', 'GetThing']
        );
    }

    public function testConstructorRejectsInvalidClientConfigClass(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'clientConfigClass must be a valid PHP class name'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            clientConfigClass: 'not a class'
        );
    }

    public function testConstructorRejectsInvalidClientBaseInterface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'clientBaseInterface must be a valid PHP class name'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            clientBaseInterface: 'not an interface'
        );
    }

    public function testConstructorRequiresConfigClassForCustomBase(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'clientConfigClass must be set when clientBaseClass is customized'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            clientBaseClass: 'Vendor\\Runtime\\VendorClient'
        );
    }

    public function testCustomBaseCanExplicitlyUseDefaultConfigClass(): void
    {
        $settings = new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            clientBaseClass: 'Vendor\\Runtime\\VendorClient',
            clientConfigClass: 'Smithy\\Client\\ClientConfig'
        );

        $this->assertSame(
            'Smithy\\Client\\ClientConfig',
            $settings->clientConfigClass
        );
    }

    public function testConstructorRejectsInvalidWaiterInitialismKey(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'waiterResourceInitialisms must map lowercase words '
                . 'to non-empty strings'
        );

        new CodegenSettings(
            namespace: 'Smithy\\Example',
            outputDir: '/tmp/output',
            waiterResourceInitialisms: ['API' => 'API']
        );
    }

    public function testFromArrayNormalizesOperationTraitIndexes(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/output',
            'operationSchemaTraits' => [
                'smithy.example#GetThing' => [
                    '27' => ['name' => 'value']
                ]
            ]
        ]);

        $this->assertSame(
            [27 => ['name' => 'value']],
            $settings->operationSchemaTraits[
                'smithy.example#GetThing'
            ]
        );
    }

    public function testFromArrayPreservesOperationTraitIds(): void
    {
        $settings = CodegenSettings::fromArray([
            'namespace' => 'Smithy\\Example',
            'outputDir' => '/tmp/output',
            'schemaTraitIndices' => [
                'vendor.example#metadata' => 27
            ],
            'operationSchemaTraits' => [
                'smithy.example#GetThing' => [
                    'vendor.example#metadata' => ['name' => 'value']
                ]
            ]
        ]);

        $this->assertSame(
            [
                'vendor.example#metadata' => ['name' => 'value']
            ],
            $settings->operationSchemaTraits[
                'smithy.example#GetThing'
            ]
        );
    }
}
