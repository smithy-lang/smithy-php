<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Smithy\Protocol\Aws\JsonErrorEnvelopeParser;

final class JsonErrorEnvelopeParserTest extends TestCase
{
    public function testPreservesConflictingErrorCandidates(): void
    {
        $envelope = JsonErrorEnvelopeParser::parse(
            '{"__type":"namespace#TypeError","code":"CodeError:detail",'
            . '"message":"Lowercase","Message":"Uppercase"}'
        );

        self::assertSame(
            [
                'type' => 'namespace#TypeError',
                'code' => 'CodeError:detail',
                'message' => 'Lowercase'
            ],
            $envelope
        );
    }

    public function testReadsCapitalizedMessage(): void
    {
        self::assertSame(
            ['type' => null, 'code' => null, 'message' => 'Bad input'],
            JsonErrorEnvelopeParser::parse('{"Message":"Bad input"}')
        );
    }

    #[DataProvider('bodiesWithoutEnvelopeFields')]
    public function testHandlesBodiesWithoutEnvelopeFields(string $body): void
    {
        self::assertSame(
            ['type' => null, 'code' => null, 'message' => ''],
            JsonErrorEnvelopeParser::parse($body)
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function bodiesWithoutEnvelopeFields(): iterable
    {
        yield 'empty body' => [''];
        yield 'empty object' => ['{}'];
        yield 'null document' => ['null'];
        yield 'scalar document' => ['42'];
    }

    public function testPreservesMalformedJsonFailure(): void
    {
        $this->expectException(\JsonException::class);

        JsonErrorEnvelopeParser::parse('{');
    }

    #[DataProvider('errorTypes')]
    public function testNormalizesLegacyErrorTypes(string $raw): void
    {
        self::assertSame(
            'FooError',
            JsonErrorEnvelopeParser::normalizeErrorType($raw)
        );
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function errorTypes(): iterable
    {
        yield 'shape name' => ['FooError'];
        yield 'suffix' => ['FooError:http://example.com'];
        yield 'namespace' => ['namespace#FooError'];
        yield 'namespace and suffix' => [
            'namespace#FooError:http://example.com'
        ];
    }
}
