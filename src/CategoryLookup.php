<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Mapping\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Component\Serializer\SerializerInterface;

final class CategoryLookup implements TransformerInterface
{
    private SerializerInterface $serializer;

    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\V2_1\Client|\Kiboko\Magento\V2_2\Client|\Kiboko\Magento\V2_3\Client|\Kiboko\Magento\V2_4\Client $client,
        private CacheInterface $cache,
        private string $cacheKey,
        private CompiledMapperInterface $mapper,
        private string $mappingField,
    ) {
        $this->serializer = new Serializer(
            normalizers: [
                new ObjectNormalizer()
            ],
            encoders: [
                new JsonEncoder()
            ]
        );
    }

    public function transform(): \Generator
    {
        $line = yield;
        while (true) {
            try {
                $lookup = $this->cache->get(sprintf($this->cacheKey, $line[$this->mappingField]));

                if ($lookup === null) {
                    $lookup = $this->client->catalogCategoryRepositoryV1GetGet(
                        categoryId: $line[$this->mappingField],
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
                        $this->serializer->serialize($lookup, null),
                    );
                }
            } catch (\RuntimeException $exception) {
                $this->logger->warning($exception->getMessage(), ['exception' => $exception, 'item' => $line]);
                $line = yield new RejectionResultBucket($line);
                continue;
            }

            $output = ($this->mapper)($lookup, $line);

            $line = yield new AcceptanceResultBucket($output);
        }
    }
}
