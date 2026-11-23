<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Protocol\Aws;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Retry\RetrySafety;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;
use Smithy\Protocol\Aws\AwsDocumentException;
use Smithy\Protocol\Aws\AwsErrorInterface;
use Smithy\Protocol\Aws\AwsExtendedRequestIdInterface;

final class AwsDocumentExceptionTest extends TestCase
{
    public function testImplementsAwsContracts(): void
    {
        $error = new AwsDocumentException($this->schema(), ['detail']);

        $this->assertInstanceOf(AwsErrorInterface::class, $error);
        $this->assertInstanceOf(
            AwsExtendedRequestIdInterface::class,
            $error
        );
    }

    public function testCarriesModeledMembersAndExceptionState(): void
    {
        $schema = $this->schema();
        $error = new AwsDocumentException(
            $schema,
            ['modeled detail'],
            'wire message',
            ErrorFault::Client
        );

        $this->assertSame($schema, $error->getSchema());
        $this->assertSame(
            'modeled detail',
            $error->getMemberValue($schema->getMember('detail'))
        );
        $this->assertSame('wire message', $error->getMessage());
        $this->assertTrue($error->isClientFault());
    }

    public function testCarriesAwsMetadata(): void
    {
        $error = new AwsDocumentException($this->schema(), ['x']);
        $error->setAwsErrorMetadata('from wire', 'NoSuchKey', 'req-1', 404);
        $error->setExtendedRequestId('host-id');

        $this->assertSame('from wire', $error->getMessage());
        $this->assertSame('NoSuchKey', $error->getAwsErrorCode());
        $this->assertSame('req-1', $error->getAwsRequestId());
        $this->assertSame(404, $error->getHttpStatusCode());
        $this->assertSame('host-id', $error->getExtendedRequestId());
    }

    public function testFromAwsErrorBuildsEmptyDocument(): void
    {
        $error = AwsDocumentException::fromAwsError(
            'boom',
            ErrorFault::Server,
            null,
            'Oops',
            'req-2',
            503
        );

        $this->assertSame('boom', $error->getMessage());
        $this->assertSame('Oops', $error->getAwsErrorCode());
        $this->assertSame('req-2', $error->getAwsRequestId());
        $this->assertSame(503, $error->getHttpStatusCode());
        $this->assertSame(RetrySafety::Yes, $error->getRetrySafety());
    }

    public function testWithMemberValueKeepsAwsMetadata(): void
    {
        $schema = $this->schema();
        $error = new AwsDocumentException(
            $schema,
            ['old'],
            'msg',
            ErrorFault::Client,
            null,
            'NoSuchKey',
            'req-3',
            404
        );
        $error->setExtendedRequestId('host-id');
        $error->setRetryAfter(2.5);

        $copy = $error->withMemberValue($schema->getMember('detail'), 'new');

        $this->assertNotSame($error, $copy);
        $this->assertSame(
            'new',
            $copy->getMemberValue($schema->getMember('detail'))
        );
        $this->assertSame('NoSuchKey', $copy->getAwsErrorCode());
        $this->assertSame('req-3', $copy->getAwsRequestId());
        $this->assertSame(404, $copy->getHttpStatusCode());
        $this->assertSame('host-id', $copy->getExtendedRequestId());
        $this->assertSame(2.5, $copy->getRetryAfter());
        $this->assertSame('msg', $copy->getMessage());
    }

    private function schema(): Schema
    {
        return new Schema(
            id: 'smithy.test#DynamicError',
            type: ShapeType::Structure,
            members: [
                new Schema(
                    id: 'smithy.api#String',
                    type: ShapeType::String_,
                    memberName: 'detail',
                    memberIndex: 0
                )
            ]
        );
    }
}
