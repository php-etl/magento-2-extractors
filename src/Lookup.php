<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\EmptyResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Mapping\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\SimpleCache\CacheInterface;

final readonly class Lookup implements TransformerInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\Client $client,
        private CacheInterface $cache,
        private string $cacheKey,
        private CompiledMapperInterface $mapper,
        private string $mappingField,
        private string $attributeCode,
    ) {
    }

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
                    $results = $this->client->getV1ProductsAttributesAttributeCodeOptions(
                        attributeCode: $this->attributeCode,
                    );

                    $lookup = array_values(array_filter($results, fn (object $item) => $item->getValue() === $line[$this->mappingField]))[0];

                    if (!$lookup instanceof \Kiboko\Magento\Model\EavDataAttributeOptionInterface) {
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
                    sprintf('Something went wrong in the attempt to recover the attribute option for attribute %s', $this->attributeCode),
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
