<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\Bucket\RejectionResultBucket;
use Kiboko\Contract\Mapping\CompiledMapperInterface;
use Kiboko\Contract\Pipeline\TransformerInterface;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Serializer\SerializerInterface;

final class FamilyLookup implements TransformerInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\V2_1\Client|\Kiboko\Magento\V2_2\Client|\Kiboko\Magento\V2_3\Client|\Kiboko\Magento\V2_4\Client $client,
        private CacheInterface $cache,
        private CompiledMapperInterface $mapper,
        private SerializerInterface $serializer,
        private string $mappingField,
    ) {
    }

    public function transform(): \Generator
    {
        $line = yield;
        while (true) {
            try {
                $lookup = $this->serializer->deserialize(
                    $this->cache->get(sprintf('family.%s', $line[$this->mappingField])),
                    \Kiboko\Magento\V2_3\Model\EavDataAttributeOptionInterface::class,
                    'json'
                );

                if ($lookup === null) {
                    $lookup = $this->client->catalogCategoryAttributeOptionManagementV1GetItemsGet(
                        attributeCode: $line[$this->mappingField],
                    );

                    if (!$lookup instanceof \Kiboko\Magento\V2_1\Model\EavDataAttributeOptionInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_2\Model\EavDataAttributeOptionInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_3\Model\EavDataAttributeOptionInterface
                        && !$lookup instanceof \Kiboko\Magento\V2_4\Model\EavDataAttributeOptionInterface
                    ) {
                        return;
                    }

                    $this->cache->set(
                        sprintf('family.%s', $line[$this->mappingField]),
                        $this->serializer->serialize($lookup, 'json'),
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
