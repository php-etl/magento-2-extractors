<?php

declare(strict_types=1);

namespace Tests\Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Flow\Magento2\CustomerExtractor;
use Kiboko\Component\Flow\Magento2\Filter;
use Kiboko\Component\Flow\Magento2\FilterGroup;
use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\Client;
use Kiboko\Magento\Model\CustomerDataCustomerInterface;
use Kiboko\Magento\Model\CustomerDataCustomerSearchResultsInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @internal
 */
#[\PHPUnit\Framework\Attributes\CoversNothing]
final class CustomerExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    #[\PHPUnit\Framework\Attributes\Test]
    public function isSuccessful(): void
    {
        $customer = (new CustomerDataCustomerInterface())
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('johndoe@example.com')
        ;

        $customer2 = (new CustomerDataCustomerInterface())
            ->setFirstname('John')
            ->setLastname('Smith')
            ->setEmail('john.smith@example.com')
        ;

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('getV1CustomersSearch')
            ->willReturn(
                (new CustomerDataCustomerSearchResultsInterface())
                    ->setItems([
                        $customer,
                        $customer2,
                    ])
                    ->setTotalCount(1)
            )
        ;

        $extractor = new CustomerExtractor(
            new NullLogger(),
            $client,
            1,
            [
                (new FilterGroup())->withFilter(new Filter('updated_at', 'eq', '2022-09-05')),
                (new FilterGroup())->withFilter(new Filter('active', 'eq', true)),
            ]
        );

        $this->assertExtractorExtractsExactly(
            [
                $customer,
                $customer2,
            ],
            $extractor
        );
    }

    public function pipelineRunner(): PipelineRunnerInterface
    {
        return new PipelineRunner();
    }
}
