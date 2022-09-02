<?php

declare(strict_types=1);

namespace Tests\Kiboko\Magento\V2\Extractor;

use Kiboko\Component\Flow\Magento2\ProductExtractor;
use Kiboko\Component\PHPUnitExtension\Assert\ExtractorAssertTrait;
use Kiboko\Component\PHPUnitExtension\PipelineRunner;
use Kiboko\Contract\Pipeline\PipelineRunnerInterface;
use Kiboko\Magento\V2_3\Client;
use Kiboko\Magento\V2_3\Model\CatalogDataProductInterface;
use Kiboko\Magento\V2_3\Model\CatalogDataProductSearchResultsInterface;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class ProductExtractorTest extends TestCase
{
    use ExtractorAssertTrait;

    public function testIsSuccessful(): void
    {
        $product = (new CatalogDataProductInterface())
            ->setSku('RDZBH')
            ->setName('My product name')
            ->setPrice(15);

        $client = $this->createMock(Client::class);
        $client
            ->expects($this->once())
            ->method('catalogProductRepositoryV1GetListGet')
            ->willReturn(
                (new CatalogDataProductSearchResultsInterface())
                    ->setItems([
                        $product
                    ])
                ->setTotalCount(1)
            );

        $extractor = new ProductExtractor(
            new NullLogger(),
            $client,
        );

        $this->assertExtractorExtractsExactly(
            [
                $product
            ],
            $extractor
        );
    }

    public function pipelineRunner(): PipelineRunnerInterface
    {
        return new PipelineRunner();
    }
}
