<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Bucket\ComplexResultBucket;
use Kiboko\Contract\Pipeline\TransformerInterface;

final class FamilyLookup implements TransformerInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\V2_1\Client|\Kiboko\Magento\V2_2\Client|\Kiboko\Magento\V2_3\Client|\Kiboko\Magento\V2_4\Client $client,
    ) {
    }

    public function transform(): \Generator
    {
        $line = yield;
        do {
            $bucket = new ComplexResultBucket();
            $output = $line;

            try {
                $lookup = $this->client->catalogCategoryAttributeOptionManagementV1GetItemsGet(
                    attributeCode: $line["Famille_de_Produit"],
                );

                if (!$lookup instanceof \Kiboko\Magento\V2_1\Model\EavDataAttributeOptionInterface
                    && !$lookup instanceof \Kiboko\Magento\V2_2\Model\EavDataAttributeOptionInterface
                    && !$lookup instanceof \Kiboko\Magento\V2_3\Model\EavDataAttributeOptionInterface
                    && !$lookup instanceof \Kiboko\Magento\V2_4\Model\EavDataAttributeOptionInterface
                ) {
                    return;
                }
            } catch (\RuntimeException $exception) {
                $this->logger->warning($exception->getMessage(), ['exception' => $exception, 'item' => $line]);
                $bucket->reject($line);
                return;
            }

            $output = (function () use ($lookup, $output) {
                $output['Famille_de_Produit'] = $lookup->getLabel();
                return $output;
            })();

            $bucket->accept($output);
        } while ($line = (yield $bucket));
    }
}
