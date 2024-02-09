<?php

declare(strict_types=1);

namespace Tests\Kiboko\Component\Flow\Magento2\Filter;

use Kiboko\Component\Flow\Magento2\Filter\ArrayFilter;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class ArrayFilterTest  extends TestCase
{
    #[Test]
    public function shouldProduceOneVariant(): void
    {
        $filter = new ArrayFilter('foo', 'in', [1, 2, 3, 4], 4);
        $this->assertCount(1, iterator_to_array($filter->getIterator(), false));
        $this->assertContains([
            'field' => 'foo',
            'value' => '1,2,3,4',
            'conditionType' => 'in',
        ], $filter);
    }

    #[Test]
    public function shouldProduceSeveralVariants(): void
    {
        $filter = new ArrayFilter('foo', 'in', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 4);
        $this->assertCount(3, iterator_to_array($filter->getIterator(), false));
        $this->assertContains([
            'field' => 'foo',
            'value' => '1,2,3,4',
            'conditionType' => 'in',
        ], $filter);
        $this->assertContains([
            'field' => 'foo',
            'value' => '5,6,7,8',
            'conditionType' => 'in',
        ], $filter);
        $this->assertContains([
            'field' => 'foo',
            'value' => '9,10,11',
            'conditionType' => 'in',
        ], $filter);
    }
}
