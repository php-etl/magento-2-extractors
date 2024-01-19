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
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\V2_1\Client|\Kiboko\Magento\V2_2\Client|\Kiboko\Magento\V2_3\Client|\Kiboko\Magento\V2_4\Client $client,
        private QueryParameters $queryParameters,
        private int $pageSize = 100,
    ) {
    }

    private function walkFilterVariants(int $currentPage = 1): \Traversable
    {
        yield from  [
            ...$this->queryParameters->walkVariants([]),
            ...[
                'searchCriteria[currentPage]' => $currentPage,
                'searchCriteria[pageSize]' => $this->pageSize,
            ],
        ];
    }

    private function applyPagination(array $parameters, int $currentPage, int $pageSize): array
    {
        return [
            ...$parameters,
            ...[
                'searchCriteria[currentPage]' => $currentPage,
                'searchCriteria[pageSize]' => $pageSize,
            ],
        ];
    }

    public function extract(): iterable
    {
        $currentPage = null;
        $pageCount = null;
        try {
            foreach ($this->queryParameters->walkVariants([]) as $parameters) {
                $currentPage = 1;
                $response = $this->client->salesInvoiceRepositoryV1GetListGet(
                    queryParameters: $this->applyPagination(iterator_to_array($parameters), $currentPage, $this->pageSize),
                );

                if (!$response instanceof \Kiboko\Magento\V2_1\Model\SalesDataInvoiceSearchResultInterface
                    && !$response instanceof \Kiboko\Magento\V2_2\Model\SalesDataInvoiceSearchResultInterface
                    && !$response instanceof \Kiboko\Magento\V2_3\Model\SalesDataInvoiceSearchResultInterface
                    && !$response instanceof \Kiboko\Magento\V2_4\Model\SalesDataInvoiceSearchResultInterface
                ) {
                    return;
                }

                yield $this->processResponse($response);
            }


            while ($currentPage++ < $pageCount) {
                $response = $this->client->salesInvoiceRepositoryV1GetListGet(
                    queryParameters: iterator_to_array($this->walkFilterVariants($currentPage)),
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
                        'queryParameters' => $this->walkFilterVariants(),
                    ],
                ],
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
        if ($response instanceof \Kiboko\Magento\V2_1\Model\ErrorResponse
            || $response instanceof \Kiboko\Magento\V2_2\Model\ErrorResponse
            || $response instanceof \Kiboko\Magento\V2_3\Model\ErrorResponse
            || $response instanceof \Kiboko\Magento\V2_4\Model\ErrorResponse
        ) {
            return new RejectionResultBucket($response->getMessage(), null, $response);
        }

        return new AcceptanceResultBucket(...$response->getItems());
    }
}
