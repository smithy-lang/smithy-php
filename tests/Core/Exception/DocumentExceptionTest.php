<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Core\Exception;

use PHPUnit\Framework\TestCase;
use Smithy\Core\Shape\Exception\DocumentException;
use Smithy\Core\Exception\ErrorFault;
use Smithy\Core\Serde\Schema;
use Smithy\Core\Shape\ShapeType;

final class DocumentExceptionTest extends TestCase
{
    public function testCarriesModeledMembersAndExceptionState(): void
    {
        $schema = new Schema(
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
        $member = $schema->getMember('detail');

        $exception = new DocumentException(
            $schema,
            ['modeled detail'],
            'wire message',
            ErrorFault::Client
        );

        $this->assertSame($schema, $exception->getSchema());
        $this->assertSame(
            'modeled detail',
            $exception->getMemberValue($member)
        );
        $this->assertSame('wire message', $exception->getMessage());
        $this->assertTrue($exception->isClientFault());
    }

    public function testCopiesModeledMembersAndPreservesExceptionState(): void
    {
        $schema = new Schema(
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
        $member = $schema->getMember('detail');
        $previous = new \RuntimeException('previous');
        $exception = new DocumentException(
            $schema,
            ['before'],
            'wire message',
            ErrorFault::Server,
            $previous
        );

        $copy = $exception->withMemberValue($member, 'after');

        $this->assertNotSame($exception, $copy);
        $this->assertSame('after', $copy->getMemberValue($member));
        $this->assertSame('before', $exception->getMemberValue($member));
        $this->assertSame('wire message', $copy->getMessage());
        $this->assertTrue($copy->isServerFault());
        $this->assertSame($previous, $copy->getPrevious());
    }
}
