<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

final class ProductExtractor implements \Kiboko\Contract\Pipeline\ExtractorInterface
{
    public function __construct(
        private \Psr\Log\LoggerInterface $logger,
        private \Kiboko\Magento\v2_1\Client|\Kiboko\Magento\v2_2\Client|\Kiboko\Magento\v2_3\Client|\Kiboko\Magento\v2_4\Client $client,
        private int $pageSize = 100,
    ) {
    }

    public function extract(): iterable
    {
        try {
            $currentPage = 1;

            $response = $this->client->catalogProductRepositoryV1GetListGet(queryParameters: [
                'searchCriteria[currentPage]' => $currentPage,
                'searchCriteria[pageSize]' => $this->pageSize,

            ], fetch: $this->client::FETCH_RESPONSE);

            if ($response instanceof \Psr\Http\Message\ResponseInterface) {
                if ($response->getStatusCode() !== 200) {
                    throw new \RuntimeException($response->getReasonPhrase());
                }

                $results = json_decode($response->getBody()->getContents(), true)['items'];

                while (!(count($results) === 0)) {
                    ++$currentPage;

                    (yield new \Kiboko\Component\Bucket\AcceptanceResultBucket($results));
                }
            }
        } catch (\Exception $exception) {
            $this->logger->alert($exception->getMessage(), ['exception' => $exception]);
        }
    }
}
