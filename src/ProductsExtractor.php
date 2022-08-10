<?php

declare(strict_types=1);

namespace Kiboko\Magento\V2\Extractor;

final class ProductsExtractor implements \Kiboko\Contract\Pipeline\ExtractorInterface
{
    public function __construct(private \Psr\Log\LoggerInterface $logger, private \Kiboko\Magento\V2\Client $client)
    {
    }

    public function extract(): iterable
    {
        try {
            $response = $this->client->catalogProductRepositoryV1GetListGet(queryParameters: [
                'searchCriteria[currentPage]' => 1,
                'searchCriteria[pageSize]' => 100,
            ], fetch: \Kiboko\Magento\V2\Client::FETCH_RESPONSE);

            if ($response instanceof \Psr\Http\Message\ResponseInterface) {
                if ($response->getStatusCode() !== 200) {
                    throw new \RuntimeException($response->getReasonPhrase());
                }

                $results = json_decode($response->getBody()->getContents(), true)['items'];

                foreach ($results as $item) {
                    (yield new \Kiboko\Component\Bucket\AcceptanceResultBucket($item));
                }
            }
        } catch (\Exception $exception) {
            $this->logger->alert($exception->getMessage(), ['exception' => $exception]);
        }
    }
}
