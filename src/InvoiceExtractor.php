<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Psr\Http\Client\NetworkExceptionInterface;

final class InvoiceExtractor implements ExtractorInterface
{
    private array $queryParameters = [
        'searchCriteria[currentPage]' => 1,
        'searchCriteria[pageSize]' => 100,
    ];

    public function __construct(
        private readonly \Psr\Log\LoggerInterface $logger,
        private readonly \Kiboko\Magento\Client $client,
        private readonly int $pageSize = 100,
        /** @var FilterGroup[] $filters */
        private readonly array $filters = [],
    ) {
    }

    private function compileQueryParameters(int $currentPage = 1): array
    {
        $parameters = $this->queryParameters;
        $parameters['searchCriteria[currentPage]'] = $currentPage;
        $parameters['searchCriteria[pageSize]'] = $this->pageSize;

        $filters = array_map(fn (FilterGroup $item, int $key) => $item->compileFilters($key), $this->filters, array_keys($this->filters));

        return array_merge($parameters, ...$filters);
    }

    public function extract(): iterable
    {
        try {
            $response = $this->client->getV1Invoices(
                queryParameters: $this->compileQueryParameters(),
            );

            if (!$response instanceof \Kiboko\Magento\Model\SalesDataInvoiceSearchResultInterface) {
                return;
            }

            yield $this->processResponse($response);

            $currentPage = 1;
            $pageCount = ceil($response->getTotalCount() / $this->pageSize);
            while ($currentPage++ < $pageCount) {
                $response = $this->client->getV1Invoices(
                    queryParameters: $this->compileQueryParameters($currentPage),
                );

                yield $this->processResponse($response);
            }
        } catch (NetworkExceptionInterface $exception) {
            $this->logger->alert(
                $exception->getMessage(),
                [
                    'exception' => $exception,
                    'context' => [
                        'path' => 'invoice',
                        'method' => 'get',
                        'queryParameters' => $this->compileQueryParameters(),
                    ],
                ]
            );
            yield new RejectionResultBucket(
                'There are some network difficulties. We could not properly connect to the Magento API. There is nothing we could no to fix this currently. Please contact the Magento administrator.',
                $exception,
            );
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
        }
    }

    private function processResponse($response): ResultBucketInterface
    {
        if ($response instanceof \Kiboko\Magento\Model\ErrorResponse) {
            return new RejectionResultBucket($response->getMessage(), null, $response);
        }

        return new AcceptanceResultBucket(...$response->getItems());
    }
}
