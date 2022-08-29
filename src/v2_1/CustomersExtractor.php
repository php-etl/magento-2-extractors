<?php

declare(strict_types=1);

namespace Kiboko\Magento\v2_1\Extractor;

use Kiboko\Magento\v2_1\Client;

final class CustomersExtractor implements \Kiboko\Contract\Pipeline\ExtractorInterface
{
    public function __construct(private \Psr\Log\LoggerInterface $logger, private Client $client)
    {
    }

    public function extract(): iterable
    {
        try {
            $response = $this->client->customerCustomerRepositoryV1GetListGet(queryParameters: [
                'searchCriteria[currentPage]' => 1,
                'searchCriteria[pageSize]' => 100,
            ], fetch: Client::FETCH_RESPONSE);

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
