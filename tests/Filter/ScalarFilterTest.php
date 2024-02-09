<?php

declare(strict_types=1);

namespace Tests\Kiboko\Component\Flow\Magento2\Filter;

use Kiboko\Component\Flow\Magento2\Filter\ScalarFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ScalarFilterTest  extends TestCase
{
    #[Test]
    public function shouldProduceOneVariant(): void
    {
        $filter = new ScalarFilter('foo', 'eq', 4);
        $this->assertCount(1, iterator_to_array($filter->getIterator(), false));
        $this->assertContains([
            'field' => 'foo',
            'value' => '4',
            'conditionType' => 'eq',
        ], $filter);
    }
}
