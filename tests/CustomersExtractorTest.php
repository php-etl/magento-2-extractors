<?php

declare(strict_types=1);

namespace Tests\Kiboko\Magento\V2\Extractor;

use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\V2\Client;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CustomersExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    public function testIsSuccessful(): void
    {
        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('customerCustomerRepositoryV1GetListGet')
            ->willReturn(
                new \GuzzleHttp\Psr7\Response(
                    200,
                    [],
                    json_encode([
                        'items' => [
                            [
                                'firstname' => 'John',
                                'lastname' => 'Doe',
                                'email' => 'johndoe@example.com',
                            ]
                        ]
                    ], JSON_THROW_ON_ERROR)
                )
            );

        $extractor = new \Kiboko\Magento\V2\Extractor\CustomersExtractor(
            new NullLogger(),
            $client,
        );

        $this->assertExtractorExtractsExactly(
            [
                [
                    'firstname' => 'John',
                    'lastname' => 'Doe',
                    'email' => 'johndoe@example.com',
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
