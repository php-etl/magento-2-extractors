<?php

declare(strict_types=1);

namespace Tests\Kiboko\Magento\V2\Extractor;

use Kiboko\Component\Flow\Magento2\Filter\ScalarFilter;
use Kiboko\Component\Flow\Magento2\FilterGroup;
use Kiboko\Component\Flow\Magento2\InvoiceExtractor;
use Kiboko\Component\Flow\Magento2\QueryParameters;
use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\V2_3\Client;
use Kiboko\Magento\V2_3\Model\SalesDataInvoiceInterface;
use Kiboko\Magento\V2_3\Model\SalesDataInvoiceSearchResultInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class InvoiceExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    public function testIsSuccessful(): void
    {
        $invoice = (new SalesDataInvoiceInterface())
            ->setBaseCurrencyCode('EUR')
            ->setTotalQty(1)
            ->setBaseGrandTotal(59.90);

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('salesInvoiceRepositoryV1GetListGet')
            ->willReturn(
                (new SalesDataInvoiceSearchResultInterface())
                    ->setItems([
                        $invoice,
                    ])
                    ->setTotalCount(1)
            );

        $extractor = new InvoiceExtractor(
            new NullLogger(),
            $client,
            (new QueryParameters())
                ->withGroup(
                    (new FilterGroup())
                        ->withFilter(new ScalarFilter('updated_at', 'eq', '2022-09-05')),
                )
                ->withGroup(
                    (new FilterGroup())
                        ->withFilter(new ScalarFilter('active', 'eq', true)),
                )
        );

        $this->assertExtractorExtractsExactly(
            [
                $invoice,
            ],
            $extractor
        );
    }

    public function pipelineRunner(): PipelineRunnerInterface
    {
        return new PipelineRunner();
    }
}
