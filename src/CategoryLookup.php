<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Mapping\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class CategoryLookup implements TransformerInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\V2_1\Client|\Kiboko\Magento\V2_2\Client|\Kiboko\Magento\V2_3\Client|\Kiboko\Magento\V2_4\Client $client,
        private CacheInterface $cache,
        private string $cacheKey,
        private CompiledMapperInterface $mapper,
        private string $mappingField,
    ) {}

    public function transform(): \Generator
    {
        $line = yield new EmptyResultBucket();
        while (true) {
            if (null === $line[$this->mappingField]) {
                $line = yield new AcceptanceResultBucket($line);
            }

            try {
                $lookup = $this->cache->get(sprintf($this->cacheKey, $line[$this->mappingField]));

                if (null === $lookup) {
                    $lookup = $this->client->catalogCategoryRepositoryV1GetGet(
                        categoryId: (int) $line[$this->mappingField],
                    );

                    if (!$lookup instanceof \Kiboko\Magento\V2_1\Model\CatalogDataCategoryInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_2\Model\CatalogDataCategoryInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_3\Model\CatalogDataCategoryInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_4\Model\CatalogDataCategoryInterface
                    ) {
                        return;
                    }

                    $this->cache->set(
                        sprintf($this->cacheKey, $line[$this->mappingField]),
                        $lookup,
                    );
                }
            } catch (\RuntimeException $exception) {
                $this->logger->warning($exception->getMessage(), ['exception' => $exception, 'item' => $line]);
                $line = yield new RejectionResultBucket(
                    sprintf('Something went wrong in the attempt to recover the category with id %d', (int) $line[$this->mappingField]),
                    $exception,
                    $line
                );
                continue;
            }

            $output = ($this->mapper)($lookup, $line);

            $line = yield new AcceptanceResultBucket($output);
        }
    }
}
