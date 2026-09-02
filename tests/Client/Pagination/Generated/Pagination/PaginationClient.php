<?php

declare(strict_types=1);

namespace Smithy\Tests\Client\Pagination\Generated\Pagination;

use Smithy\Client\AbstractClient;
use Smithy\Client\Pagination\Paginator;
use Smithy\Tests\Client\Pagination\Generated\Pagination\Input\{
    ListMapsInput,
    ListStringsInput,
    ListWithoutPageSizeInput
};
use Smithy\Tests\Client\Pagination\Generated\Pagination\Operation\{
    ListMapsOperation,
    ListStringsOperation,
    ListWithoutPageSizeOperation
};
use Smithy\Tests\Client\Pagination\Generated\Pagination\Output\{
    ListMapsOutput,
    ListStringsOutput,
    ListWithoutPageSizeOutput
};
/**
 * Client for the Pagination service.
 *
 * Emitted by ClientGenerator. Every operation method is a thin
 * typed wrapper around {@see AbstractClient::executeOperation()};
 * the pipeline, retry loop, endpoint resolver, signer, and
 * transport all live on the base class.
 */
final class PaginationClient extends AbstractClient
{
    public function listMaps(ListMapsInput $input): ListMapsOutput
    {
        /** @var ListMapsOutput */
        return $this->executeOperation(
            ListMapsOperation::instance(),
            $input
        );
    }

    /**
     * Create a paginator for {@see self::listMaps()}.
     *
     * @return Paginator<ListMapsOutput>
     */
    public function listMapsPaginated(ListMapsInput $input): Paginator
    {
        return $this->createPaginator(
            ListMapsOperation::instance(),
            $input
        );
    }

    public function listStrings(ListStringsInput $input): ListStringsOutput
    {
        /** @var ListStringsOutput */
        return $this->executeOperation(
            ListStringsOperation::instance(),
            $input
        );
    }

    /**
     * Create a paginator for {@see self::listStrings()}.
     *
     * @return Paginator<ListStringsOutput>
     */
    public function listStringsPaginated(ListStringsInput $input): Paginator
    {
        return $this->createPaginator(
            ListStringsOperation::instance(),
            $input
        );
    }

    public function listWithoutPageSize(ListWithoutPageSizeInput $input): ListWithoutPageSizeOutput
    {
        /** @var ListWithoutPageSizeOutput */
        return $this->executeOperation(
            ListWithoutPageSizeOperation::instance(),
            $input
        );
    }

    /**
     * Create a paginator for {@see self::listWithoutPageSize()}.
     *
     * @return Paginator<ListWithoutPageSizeOutput>
     */
    public function listWithoutPageSizePaginated(ListWithoutPageSizeInput $input): Paginator
    {
        return $this->createPaginator(
            ListWithoutPageSizeOperation::instance(),
            $input
        );
    }
}
