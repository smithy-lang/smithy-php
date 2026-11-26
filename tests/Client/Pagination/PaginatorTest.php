<?php

/*
 * Copyright Amazon.com, Inc. or its affiliates. All Rights Reserved.
 * SPDX-License-Identifier: Apache-2.0
 */

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination;

use PHPUnit\Framework\TestCase;
use Smithy\Client\RequestOverrideConfig;
use Smithy\Client\Pagination\Paginator;
use Smithy\Tests\Client\Pagination\Fixture\{
    InvalidPaginationOperation,
    MockClient,
    NotPaginatedOperation,
    PaginationInput,
    PaginationOperation,
    PaginationOperationWithoutItems,
    PaginationOperationWithoutPageSize,
    PaginationOutput,
    PaginationResult
};
final class PaginatorTest extends TestCase
{
    public function testIteratesLazilyAndPropagatesNestedTokens(): void
    {
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult('one')),
            new PaginationOutput('second', new PaginationResult('two')),
            new PaginationOutput('third', new PaginationResult(null))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $this->assertSame([], $client->tokens);

        $pages = \iterator_to_array($paginator, false);

        $this->assertSame(
            ['first', 'second', 'third'],
            \array_map(
                static fn(PaginationOutput $page): string => $page->value,
                $pages
            )
        );
        $this->assertSame([null, 'one', 'two'], $client->tokens);
    }

    public function testRejectsPageOfAnotherOutputClass(): void
    {
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult(null))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput(),
            PaginationResult::class
        );

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessage(
            'returned ' . PaginationOutput::class
                . ' instead of ' . PaginationResult::class . '.'
        );

        \iterator_to_array($paginator, false);
    }

    public function testSupportsMapTokensAndStopsOnEmptyMap(): void
    {
        $token = ['pk' => ['S' => 'one']];
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult($token)),
            new PaginationOutput('second', new PaginationResult([]))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(2, $pages);
        $this->assertSame([null, $token], $client->tokens);
    }

    public function testMapTokenKeyOrderDoesNotTriggerAnotherRequest(): void
    {
        $firstToken = ['partition' => 'one', 'sort' => 'two'];
        $reorderedToken = ['sort' => 'two', 'partition' => 'one'];
        $client = new MockClient([
            new PaginationOutput(
                'first',
                new PaginationResult($firstToken)
            ),
            new PaginationOutput(
                'repeated',
                new PaginationResult($reorderedToken)
            ),
            new PaginationOutput(
                'unused',
                new PaginationResult(null)
            )
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(2, $pages);
        $this->assertSame([null, $firstToken], $client->tokens);
    }

    public function testMapTokenComparisonPreservesValueTypes(): void
    {
        $stringToken = ['value' => '1'];
        $integerToken = ['value' => 1];
        $client = new MockClient([
            new PaginationOutput(
                'first',
                new PaginationResult($stringToken)
            ),
            new PaginationOutput(
                'second',
                new PaginationResult($integerToken)
            ),
            new PaginationOutput(
                'third',
                new PaginationResult(null)
            )
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(3, $pages);
        $this->assertSame(
            [null, $stringToken, $integerToken],
            $client->tokens
        );
    }

    public function testStopsAfterYieldingARepeatedTokenPage(): void
    {
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult('same')),
            new PaginationOutput('second', new PaginationResult('same')),
            new PaginationOutput('unused', new PaginationResult(null))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(2, $pages);
        $this->assertSame([null, 'same'], $client->tokens);
    }

    public function testStopsWhenResponseRepeatsResumeToken(): void
    {
        $client = new MockClient([
            new PaginationOutput('resumed', new PaginationResult('resume')),
            new PaginationOutput('repeated', new PaginationResult('resume')),
            new PaginationOutput('unused', new PaginationResult(null))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput('resume')
        );

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(1, $pages);
        $this->assertSame(['resume'], $client->tokens);
    }

    public function testDisabledStopOnSameTokenContinuesUntilEmptyToken(): void
    {
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult('same')),
            new PaginationOutput('second', new PaginationResult('same')),
            new PaginationOutput('third', new PaginationResult('same')),
            new PaginationOutput('last', new PaginationResult(null)),
            new PaginationOutput('unused', new PaginationResult(null))
        ]);
        $paginator = (new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        ))->withStopOnSameToken(false);

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(4, $pages);
        $this->assertSame([null, 'same', 'same', 'same'], $client->tokens);
    }

    public function testDisabledStopOnSameTokenContinuesPastResumeToken(): void
    {
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult('resume')),
            new PaginationOutput('last', new PaginationResult(null))
        ]);
        $paginator = (new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput('resume')
        ))->withStopOnSameToken(false);

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(2, $pages);
        $this->assertSame(['resume', 'resume'], $client->tokens);
    }

    public function testDisabledStopOnSameTokenSupportsMapTokens(): void
    {
        $token = ['pk' => ['S' => 'one']];
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult($token)),
            new PaginationOutput('second', new PaginationResult($token)),
            new PaginationOutput('last', new PaginationResult([]))
        ]);
        $paginator = (new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        ))->withStopOnSameToken(false);

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(3, $pages);
        $this->assertSame([null, $token, $token], $client->tokens);
    }

    public function testDisabledStopOnSameTokenStillHonorsMaxItems(): void
    {
        $client = new MockClient([
            new PaginationOutput(
                'first',
                new PaginationResult('same', ['a', 'b'])
            ),
            new PaginationOutput(
                'second',
                new PaginationResult('same', ['c', 'd'])
            ),
            new PaginationOutput(
                'unused',
                new PaginationResult('same', ['e', 'f'])
            )
        ]);
        $paginator = (new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput(pageSize: 2)
        ))->withStopOnSameToken(false)->withMaxItems(4);

        $pages = \iterator_to_array($paginator, false);

        $this->assertCount(2, $pages);
        $this->assertSame([2, 2], $client->pageSizes);
    }

    public function testWithStopOnSameTokenReturnsNewInstance(): void
    {
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult('same')),
            new PaginationOutput('second', new PaginationResult('same')),
            new PaginationOutput('third', new PaginationResult(null))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $continuing = $paginator->withStopOnSameToken(false);

        $this->assertNotSame($paginator, $continuing);
        $this->assertCount(2, \iterator_to_array($paginator, false));
        $this->assertSame([null, 'same'], $client->tokens);
    }

    public function testPaginatorCanBeIteratedMoreThanOnce(): void
    {
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult(null)),
            new PaginationOutput('second', new PaginationResult(null))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $first = \iterator_to_array($paginator, false);
        $second = \iterator_to_array($paginator, false);

        $this->assertSame('first', $first[0]->value);
        $this->assertSame('second', $second[0]->value);
        $this->assertSame([null, null], $client->tokens);
    }

    public function testRequestOverridesApplyToEveryPage(): void
    {
        $overrides = RequestOverrideConfig::fromArray([
            '@http' => ['headers' => ['x-paginator' => 'value']]
        ]);
        $client = new MockClient([
            new PaginationOutput('first', new PaginationResult('next')),
            new PaginationOutput('second', new PaginationResult(null))
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $configured = $paginator->withRequestOverrides($overrides);

        $this->assertNotSame($paginator, $configured);
        \iterator_to_array($configured, false);
        $this->assertSame([$overrides, $overrides], $client->overrides);
    }

    public function testMaxItemsShrinksFinalRequestAndStops(): void
    {
        $client = new MockClient([
            new PaginationOutput(
                'first',
                new PaginationResult('one', ['a', 'b', 'c', 'd'])
            ),
            new PaginationOutput(
                'second',
                new PaginationResult('two', ['e', 'f', 'g', 'h'])
            ),
            new PaginationOutput(
                'third',
                new PaginationResult('unused', ['i', 'j'])
            ),
            new PaginationOutput(
                'base',
                new PaginationResult(null, ['k'])
            )
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput(pageSize: 4)
        );

        $limited = $paginator->withMaxItems(10);

        $this->assertNotSame($paginator, $limited);
        $this->assertCount(
            3,
            \iterator_to_array($limited, false)
        );
        $this->assertSame([4, 4, 2], $client->pageSizes);

        $this->assertCount(
            1,
            \iterator_to_array($paginator, false)
        );
        $this->assertSame([4, 4, 2, 4], $client->pageSizes);
    }

    public function testMaxItemsContinuesAfterFilteredEmptyPage(): void
    {
        $client = new MockClient([
            new PaginationOutput(
                'empty',
                new PaginationResult('one', [])
            ),
            new PaginationOutput(
                'second',
                new PaginationResult('two', ['a', 'b'])
            ),
            new PaginationOutput(
                'third',
                new PaginationResult('unused', ['c'])
            )
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput(pageSize: 2)
        );

        $pages = \iterator_to_array(
            $paginator->withMaxItems(3),
            false
        );

        $this->assertCount(3, $pages);
        $this->assertSame([2, 2, 1], $client->pageSizes);
    }

    public function testMaxItemsUsesRemainingWhenPageSizeIsUnset(): void
    {
        $client = new MockClient([
            new PaginationOutput(
                'only',
                new PaginationResult('unused', ['a', 'b', 'c'])
            )
        ]);
        $paginator = new Paginator(
            $client,
            PaginationOperation::instance(),
            new PaginationInput()
        );

        \iterator_to_array($paginator->withMaxItems(3), false);

        $this->assertSame([3], $client->pageSizes);
    }

    public function testMaxItemsMustBePositive(): void
    {
        $paginator = new Paginator(
            new MockClient([]),
            PaginationOperation::instance(),
            new PaginationInput()
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage(
            'Paginator max items must be greater than zero.'
        );

        $paginator->withMaxItems(0);
    }

    public function testMaxItemsRequiresModeledItems(): void
    {
        $paginator = new Paginator(
            new MockClient([]),
            new PaginationOperationWithoutItems(),
            new PaginationInput()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Operation smithy.tests#PaginateWithoutItems does not model '
            . 'pagination items.'
        );

        $paginator->withMaxItems(1);
    }

    public function testMaxItemsRequiresModeledPageSize(): void
    {
        $paginator = new Paginator(
            new MockClient([]),
            new PaginationOperationWithoutPageSize(),
            new PaginationInput()
        );

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Operation smithy.tests#PaginateWithoutPageSize does not model '
            . 'a pagination page size.'
        );

        $paginator->withMaxItems(1);
    }

    public function testRejectsOperationWithoutPaginationMetadata(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            'Operation smithy.tests#NotPaginated is not paginated.'
        );

        new Paginator(
            new MockClient([]),
            NotPaginatedOperation::instance(),
            new PaginationInput()
        );
    }

    public function testRejectsInvalidOutputTokenPath(): void
    {
        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage(
            "pagination output token path 'missing.token' "
            . "does not resolve at 'missing'."
        );

        new Paginator(
            new MockClient([]),
            InvalidPaginationOperation::instance(),
            new PaginationInput()
        );
    }
}
