<?php

declare(strict_types=1);

namespace Tests\Kiboko\Magento\V2\Extractor;

use Kiboko\Component\Flow\Magento2\ProductExtractor;
use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\v2_3\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ProductsExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    public function testIsSuccessful(): void
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('catalogProductRepositoryV1GetListGet')
            ->willReturn(
                new \GuzzleHttp\Psr7\Response(
                    200,
                    [],
                    json_encode([
                        'items' => [
                           [
                               'sku' => "123456",
                                'name' => "Agendas année civile Semainier Equology",
                                'price' => 10,
                           ]
                        ]
                    ], JSON_THROW_ON_ERROR)
                )
            );

        $extractor = new ProductExtractor(
            new NullLogger(),
            $client,
        );

        $this->assertExtractorExtractsExactly(
            [
                [
                    'sku' => "123456",
                    'name' => "Agendas année civile Semainier Equology",
                    'price' => 10,
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
