<?php

declare(strict_types=1);

namespace Tests\Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Flow\Magento2\OrderExtractor;
use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\Model\SalesDataOrderInterface;
use Kiboko\Magento\Model\SalesDataOrderSearchResultInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

/**
 * @internal
 *
 * @coversNothing
 */
final class OrderExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    /**
     * @test
     */
    public function isSuccessful(): void
    {
        $order = (new SalesDataOrderInterface())
            ->setEntityId(1)
            ->setCustomerId(10)
            ->setTotalQtyOrdered(3)
        ;

        $client = $this->createMock(\Kiboko\Magento\Client::class);
        $client
            ->expects($this->once())
            ->method('getV1Orders')
            ->willReturn(
                (new SalesDataOrderSearchResultInterface())
                    ->setItems([
                        $order,
                    ])
                    ->setTotalCount(1)
            )
        ;

        $extractor = new OrderExtractor(
            new NullLogger(),
            $client,
        );

        $this->assertExtractorExtractsExactly(
            [
                $order,
            ],
            $extractor
        );
    }

    public function pipelineRunner(): PipelineRunnerInterface
    {
        return new PipelineRunner();
    }
}
