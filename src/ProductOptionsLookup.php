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
use Kiboko\Magento\Exception\GetV1ProductsAttributesAttributeCodeOptionsBadRequestException;
use Kiboko\Magento\Exception\UnexpectedStatusCodeException;
use Kiboko\Magento\Model\EavDataAttributeOptionInterface;
use Kiboko\Magento\Model\ErrorResponse;
use Psr\Http\Client\NetworkExceptionInterface;
use Psr\Log\LoggerInterface;

/**
 * @template InputType of array
 * @template OutputType of InputType|array
 * @implements TransformerInterface<InputType, OutputType>
 */
final readonly class ProductOptionsLookup implements TransformerInterface
{
    /**
     * @param CompiledMapperInterface<EavDataAttributeOptionInterface, InputType, OutputType> $mapper
     */
    public function __construct(
        private LoggerInterface $logger,
        private Client $client,
        private CompiledMapperInterface $mapper,
        private string $mappingField,
        private string $attributeCode,
    ) {
    }

    /**
     * @return RejectionResultBucketInterface<OutputType>
     */
    private function rejectErrorResponse(ErrorResponse $response): RejectionResultBucketInterface
    {
        $this->logger->error(
            $response->getMessage(),
            [
                'resource' => 'getV1ProductsAttributesAttributeCodeOptions',
                'method' => 'get',
            ],
        );
        return new RejectionResultBucket($response->getMessage(), null);
    }

    /**
     * @return RejectionResultBucketInterface<OutputType>
     */
    private function rejectInvalidResponse(): RejectionResultBucketInterface
    {
        $this->logger->error(
            $message = 'The result provided by the API client does not match the expected type. The connector compilation may have fetched incompatible versions.',
            [
                'resource' => 'getV1ProductsAttributesAttributeCodeOptions',
                'method' => 'get',
            ],
        );
        return new RejectionResultBucket($message, null);
    }

    /**
     * @param InputType $line
     * @return OutputType
     */
    public function passThrough(array $line): array
    {
        /** @var OutputType $line */
        return $line;
    }

    public function transform(): \Generator
    {
        $line = yield new EmptyResultBucket();
        while (true) {
            if ($line === null) {
                $line = yield new EmptyResultBucket();
                continue;
            }

            if (null === $line[$this->mappingField]) {
                $line = yield new AcceptanceResultBucket($this->passThrough($line));
                continue;
            }

            try {
                $lookup = $this->client->getV1ProductsAttributesAttributeCodeOptions(
                    attributeCode: $this->attributeCode,
                );

                if ($lookup instanceof ErrorResponse) {
                    $line = yield $this->rejectErrorResponse($lookup);
                    continue;
                }

                if (!is_array($lookup) || !array_is_list($lookup)) {
                    $line = yield $this->rejectInvalidResponse();
                    continue;
                }

                $lookup = array_filter(
                    $lookup,
                    fn (object $item) => $item->getValue() === $line[$this->mappingField],
                );
            } catch (NetworkExceptionInterface $exception) {
                $this->logger->critical(
                    $exception->getMessage(),
                    [
                        'exception' => $exception,
                        'resource' => 'getV1ProductsAttributesAttributeCodeOptions',
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
            } catch (GetV1ProductsAttributesAttributeCodeOptionsBadRequestException $exception) {
                $this->logger->error(
                    $exception->getMessage(),
                    [
                        'exception' => $exception,
                        'resource' => 'getV1ProductsAttributesAttributeCodeOptions',
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
                        'resource' => 'getV1ProductsAttributesAttributeCodeOptions',
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

            reset($lookup);
            $current = current($lookup);
            if (count($lookup) <= 0 || $current === false) {
                $this->logger->critical(
                    'The lookup did not find any related resource. The lookup operation had no effect.',
                    [
                        'resource' => 'getV1ProductsAttributesAttributeCodeOptions',
                        'method' => 'get',
                        'categoryId' => (int) $line[$this->mappingField],
                        'mappingField' => $this->mappingField,
                    ],
                );
                $line = yield new AcceptanceResultBucket($this->passThrough($line));
                continue;
            }
            $output = ($this->mapper)($current, $line);

            $line = yield new AcceptanceResultBucket($output);
        }
    }
}
