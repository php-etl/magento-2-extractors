<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Bucket\RejectionResultBucketInterface;
use Kiboko\Contract\Mapping\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Kiboko\Magento\Client;
use Kiboko\Magento\Exception\GetV1CategoriesCategoryIdBadRequestException;
use Kiboko\Magento\Exception\UnexpectedStatusCodeException;
use Kiboko\Magento\Model\CatalogDataCategoryInterface;
use Kiboko\Magento\Model\ErrorResponse;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * @template InputType of array
 * @template OutputType of InputType|array
 *
 * @implements TransformerInterface<InputType>
 */
final readonly class CategoryLookup implements TransformerInterface
{
    /**
     * @param CompiledMapperInterface<CatalogDataCategoryInterface, InputType, OutputType> $mapper
     */
    public function __construct(
        private LoggerInterface $logger,
        private Client $client,
        private CompiledMapperInterface $mapper,
        private string $mappingField,
    ) {
    }

    /**
     * @return RejectionResultBucketInterface<string|null>
     */
    private function rejectErrorResponse(ErrorResponse $response): RejectionResultBucketInterface
    {
        $this->logger->error(
            $response->getMessage(),
            [
                'resource' => 'getV1CategoriesCategoryId',
                'method' => 'get',
            ],
        );

        return new RejectionResultBucket($response->getMessage(), null);
    }

    /**
     * @return RejectionResultBucketInterface<string|null>
     */
    private function rejectInvalidResponse(): RejectionResultBucketInterface
    {
        $this->logger->error(
            $message = 'The result provided by the API client does not match the expected type. The connector compilation may have fetched incompatible versions.',
            [
                'resource' => 'getV1CategoriesCategoryId',
                'method' => 'get',
            ],
        );

        return new RejectionResultBucket($message, null);
    }

    /**
     * @param array<InputType> $line
     *
     * @return OutputType
     */
    public function passThrough(array $line): array
    {
        /* @var OutputType $line */
        return $line;
    }

    public function transform(): \Generator
    {
        $line = yield new EmptyResultBucket();
        while (true) {
            if (null === $line) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            if (null === $line[$this->mappingField]) {
                $line = yield new AcceptanceResultBucket($line);
                continue;
            }

            try {
                $lookup = $this->client->getV1CategoriesCategoryId(
                    categoryId: (int) $line[$this->mappingField],
                );

                if ($lookup instanceof ErrorResponse) {
                    $line = yield $this->rejectErrorResponse($lookup);
                    continue;
                }

                if (!$lookup instanceof CatalogDataCategoryInterface) {
                    $line = yield $this->rejectInvalidResponse();
                    continue;
                }
            } catch (NetworkExceptionInterface $exception) {
                $this->logger->critical(
                    $exception->getMessage(),
                    [
                        'exception' => $exception,
                        'resource' => 'getV1CategoriesCategoryId',
                        'method' => 'get',
                        'categoryId' => (int) $line[$this->mappingField],
                        'mappingField' => $this->mappingField,
                    ],
                );
                $line = yield new RejectionResultBucket(
                    'There are some network difficulties. We could not properly connect to the Magento API. There is nothing we could no to fix this currently. Please contact the Magento administrator.',
                    $exception,
                    $this->passThrough($line),
                );
                continue;
            } catch (GetV1CategoriesCategoryIdBadRequestException $exception) {
                $this->logger->error(
                    $exception->getMessage(),
                    [
                        'exception' => $exception,
                        'resource' => 'getV1CategoriesCategoryId',
                        'method' => 'get',
                        'categoryId' => (int) $line[$this->mappingField],
                        'mappingField' => $this->mappingField,
                    ],
                );
                $line = yield new RejectionResultBucket(
                    'The source API rejected our request. Ignoring line. Maybe you are requesting on incompatible versions.',
                    $exception,
                    $this->passThrough($line),
                );
                continue;
            } catch (UnexpectedStatusCodeException $exception) {
                $this->logger->critical(
                    $exception->getMessage(),
                    [
                        'exception' => $exception,
                        'resource' => 'getV1CategoriesCategoryId',
                        'method' => 'get',
                        'categoryId' => (int) $line[$this->mappingField],
                        'mappingField' => $this->mappingField,
                    ],
                );
                $line = yield new RejectionResultBucket(
                    'The source API responded with a status we did not expect. Aborting. Please check the availability of the source API and if there are no rate limiting or redirections active.',
                    $exception,
                    $this->passThrough($line),
                );

                return;
            }

            $output = ($this->mapper)($lookup, $line);

            $line = yield new AcceptanceResultBucket($output);
        }
    }
}
