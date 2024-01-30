<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Bucket\RejectionResultBucketInterface;
use Kiboko\Contract\Bucket\ResultBucketInterface;
use Kiboko\Contract\Pipeline\ExtractorInterface;
use Kiboko\Magento\Client;
use Kiboko\Magento\Exception\GetV1CustomersSearchInternalServerErrorException;
use Kiboko\Magento\Exception\GetV1CustomersSearchUnauthorizedException;
use Kiboko\Magento\Exception\UnexpectedStatusCodeException;
use Kiboko\Magento\Model\CustomerDataCustomerInterface;
use Kiboko\Magento\Model\CustomerDataCustomerSearchResultsInterface;
use Kiboko\Magento\Model\ErrorResponse;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * @implements ExtractorInterface<CustomerDataCustomerInterface>
 */
final readonly class CustomerExtractor implements ExtractorInterface
{
    public function __construct(
        private LoggerInterface $logger,
        private Client $client,
        private QueryParameters $queryParameters,
        private int $pageSize = 100,
    ) {
    }

    /**
     * @param array<string,string> $parameters
     * @return array<string,string>
     */
    private function applyPagination(array $parameters, int $currentPage, int $pageSize): array
    {
        return [
            ...$parameters,
            'searchCriteria[currentPage]' => (string) $currentPage,
            'searchCriteria[pageSize]' => (string) $pageSize,
        ];
    }

    /**
     * @param array<string,string> $parameters
     * @return RejectionResultBucketInterface<CustomerDataCustomerInterface>
     */
    private function rejectErrorResponse(ErrorResponse $response, array $parameters, int $currentPage): RejectionResultBucketInterface
    {
        $this->logger->error(
            $response->getMessage(),
            [
                'resource' => 'getV1CustomersSearch',
                'method' => 'get',
                'queryParameters' => $parameters,
                'currentPage' => $currentPage,
                'pageSize' => $this->pageSize,
            ],
        );
        return new RejectionResultBucket($response->getMessage(), null);
    }

    /**
     * @param array<string,string> $parameters
     * @return RejectionResultBucketInterface<CustomerDataCustomerInterface>
     */
    private function rejectInvalidResponse(array $parameters, int $currentPage): RejectionResultBucketInterface
    {
        $this->logger->error(
            $message = 'The result provided by the API client does not match the expected type. The connector compilation may have fetched incompatible versions.',
            [
                'resource' => 'getV1CustomersSearch',
                'method' => 'get',
                'queryParameters' => $parameters,
                'currentPage' => $currentPage,
                'pageSize' => $this->pageSize,
            ],
        );
        return new RejectionResultBucket($message, null);
    }

    public function extract(): iterable
    {
        foreach ($this->queryParameters->walkVariants() as $parameters) {
            try {
                $currentPage = 1;
                $response = $this->client->getV1CustomersSearch(
                    queryParameters: $this->applyPagination($parameters, $currentPage, $this->pageSize),
                );
                if ($response instanceof ErrorResponse) {
                    yield $this->rejectErrorResponse($response, $parameters, $currentPage);
                    return;
                }
                if (!$response instanceof CustomerDataCustomerSearchResultsInterface) {
                    yield $this->rejectInvalidResponse($parameters, $currentPage);
                    return;
                }
                $pageCount = (int) ceil($response->getTotalCount() / $this->pageSize);

                yield new AcceptanceResultBucket(...$response->getItems());

                while ($currentPage++ < $pageCount) {
                    $response = $this->client->getV1CustomersSearch(
                        queryParameters: $this->applyPagination($parameters, $currentPage, $this->pageSize),
                    );
                    if ($response instanceof ErrorResponse) {
                        yield $this->rejectErrorResponse($response, $parameters, $currentPage);
                        return;
                    }
                    if (!$response instanceof CustomerDataCustomerSearchResultsInterface) {
                        yield $this->rejectInvalidResponse($parameters, $currentPage);
                        return;
                    }

                    yield new AcceptanceResultBucket(...$response->getItems());
                }
            } catch (NetworkExceptionInterface $exception) {
                $this->logger->critical(
                    $exception->getMessage(),
                    [
                        'exception' => $exception,
                        'resource' => 'getV1CustomersSearch',
                        'method' => 'get',
                        'queryParameters' => $parameters,
                        'currentPage' => $currentPage,
                        'pageSize' => $this->pageSize,
                    ],
                );
                yield new RejectionResultBucket(
                    'There are some network difficulties. We could not properly connect to the Magento API. There is nothing we could no to fix this currently. Please contact the Magento administrator.',
                    $exception,
                );
                return;
            } catch (GetV1CustomersSearchUnauthorizedException $exception) {
                $this->logger->warning($exception->getMessage(), ['exception' => $exception]);
                yield new RejectionResultBucket(
                    'The source API responded we are not authorized to access this resource. Aborting. Please check the credentials you provided.',
                    $exception,
                );
                return;
            } catch (GetV1CustomersSearchInternalServerErrorException $exception) {
                $this->logger->error($exception->getMessage(), ['exception' => $exception]);
                yield new RejectionResultBucket(
                    'The source API responded it is currently unavailable due to an internal error. Aborting. Please check the availability of the source API.',
                    $exception,
                );
                return;
            } catch (UnexpectedStatusCodeException $exception) {
                $this->logger->critical($exception->getMessage(), ['exception' => $exception]);
                yield new RejectionResultBucket(
                    'The source API responded with a status we did not expect. Aborting. Please check the availability of the source API and if there are no rate limiting or redirections active.',
                    $exception,
                );
                return;
            } catch (\Throwable $exception) {
                $this->logger->emergency($exception->getMessage(), ['exception' => $exception]);
                yield new RejectionResultBucket(
                    'The client failed critically. Aborting. Please contact customer support or your system administrator.',
                    $exception,
                );
                return;
            }
        }
    }
}
