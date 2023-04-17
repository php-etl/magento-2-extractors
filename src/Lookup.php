<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Mapping\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class Lookup implements TransformerInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\V2_1\Client|\Kiboko\Magento\V2_2\Client|\Kiboko\Magento\V2_3\Client|\Kiboko\Magento\V2_4\Client $client,
        private \Psr\SimpleCache\CacheInterface $cache,
        private string $cacheKey,
        private CompiledMapperInterface $mapper,
        private string $mappingField,
        private string $attributeCode,
    ) {
    }

    public function transform(): \Generator
    {
        $line = yield;
        while (true) {
            if (null === $line[$this->mappingField]) {
                $line = yield new AcceptanceResultBucket($line);
            }

            try {
                $lookup = $this->cache->get(sprintf($this->cacheKey, $line[$this->mappingField]));

                if (null === $lookup) {
                    $results = $this->client->catalogProductAttributeOptionManagementV1GetItemsGet(
                        attributeCode: $this->attributeCode,
                    );

                    $lookup = array_values(array_filter($results, fn (object $item) => $item->getValue() === $line[$this->mappingField]))[0];

                    if (!$lookup instanceof \Kiboko\Magento\V2_1\Model\EavDataAttributeOptionInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_2\Model\EavDataAttributeOptionInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_3\Model\EavDataAttributeOptionInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_4\Model\EavDataAttributeOptionInterface
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
                $line = yield new RejectionResultBucket($line);
                continue;
            }

            $output = ($this->mapper)($lookup, $line);

            $line = yield new AcceptanceResultBucket($output);
        }
    }
}
