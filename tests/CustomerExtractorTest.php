<?php

declare(strict_types=1);

namespace Tests\Kiboko\Magento\V2\Extractor;

use Kiboko\Component\Flow\Magento2\CustomerExtractor;
use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\V2_3\Client;
use Kiboko\Magento\V2_3\Model\CustomerDataCustomerInterface;
use Kiboko\Magento\V2_3\Model\CustomerDataCustomerSearchResultsInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CustomerExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    public function testIsSuccessful(): void
    {
        $customer = (new CustomerDataCustomerInterface())
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('johndoe@example.com');

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('customerCustomerRepositoryV1GetListGet')
            ->willReturn(
                (new CustomerDataCustomerSearchResultsInterface())
                    ->setItems([
                        $customer
                    ])
                    ->setTotalCount(1)
            );

        $extractor = new CustomerExtractor(
            new NullLogger(),
            $client,
        );

        $this->assertExtractorExtractsExactly(
            [
                $customer
            ],
            $extractor
        );
    }

    public function pipelineRunner(): PipelineRunnerInterface
    {
        return new PipelineRunner();
    }
}
