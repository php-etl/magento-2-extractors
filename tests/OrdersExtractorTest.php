<?php

declare(strict_types=1);

namespace Tests\Kiboko\Magento\V2\Extractor;

use Kiboko\Component\Bucket\AcceptanceResultBucket;
use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\V2\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class OrdersExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    public function testIsSuccessful(): void
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('salesOrderRepositoryV1GetListGet')
            ->willReturn(
                new \GuzzleHttp\Psr7\Response(
                    200,
                    [],
                    json_encode([
                        'items' => [
                            [
                                'customer_email' => "johndoe@example.com",
                                'customer_firstname' => "John",
                                'state' => 'canceled',
                            ]
                        ]
                    ], JSON_THROW_ON_ERROR)
                )
            );

        $extractor = new \Kiboko\Magento\V2\Extractor\OrdersExtractor(
            new NullLogger(),
            $client,
        );

        $this->assertExtractorExtractsExactly(
            [
                [
                    'customer_email' => "johndoe@example.com",
                    'customer_firstname' => "John",
                    'state' => 'canceled',
                ]
            ],
            $extractor
        );
    }

    public function pipelineRunner(): PipelineRunnerInterface
    {
        return new PipelineRunner();
    }
}
