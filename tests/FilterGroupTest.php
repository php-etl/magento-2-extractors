<?php

declare(strict_types=1);

namespace Tests\Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Flow\Magento2\Filter\ArrayFilter;
use Kiboko\Component\Flow\Magento2\FilterGroup;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

final class FilterGroupTest extends TestCase
{
    #[Test]
    public function shouldProduceOneVariant(): void
    {
        $group = new FilterGroup();
        $group->withFilter(
            new ArrayFilter('foo', 'in', [1, 2, 3, 4], 4),
        );
        $this->assertCount(1, iterator_to_array($group->walkFilters([]), false));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
        ], $group->walkFilters([]));
    }

    #[Test]
    public function shouldProduceSeveralVariants(): void
    {
        $group = new FilterGroup();
        $group->withFilter(
            new ArrayFilter('foo', 'in', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 4),
        );
        $this->assertCount(3, iterator_to_array($group->walkFilters([]), false));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '9,10,11',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
        ], $group->walkFilters([]));
    }

    #[Test]
    public function shouldProduceDemultipliedVariants(): void
    {
        $group = new FilterGroup();
        $group->withFilter(
            new ArrayFilter('foo', 'in', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 4),
        );
        $group->withFilter(
            new ArrayFilter('bar', 'in', [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15], 4),
        );
        $this->assertCount(12, iterator_to_array($group->walkFilters([]), false));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '9,10,11',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '9,10,11',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '9,10,11,12',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '9,10,11,12',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '9,10,11',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '9,10,11,12',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '1,2,3,4',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '13,14,15',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '5,6,7,8',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '13,14,15',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
        $this->assertContains([
            'searchCriteria[filterGroups][0][filters][1][field]' => 'foo',
            'searchCriteria[filterGroups][0][filters][1][value]' => '9,10,11',
            'searchCriteria[filterGroups][0][filters][1][conditionType]' => 'in',
            'searchCriteria[filterGroups][0][filters][2][field]' => 'bar',
            'searchCriteria[filterGroups][0][filters][2][value]' => '13,14,15',
            'searchCriteria[filterGroups][0][filters][2][conditionType]' => 'in',
        ], $group->walkFilters([]));
    }
}
