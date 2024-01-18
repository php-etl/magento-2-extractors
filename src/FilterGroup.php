<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

use Kiboko\Component\Flow\Magento2\Filter\FilterInterface;
use Kiboko\Component\Flow\Magento2\Filter\ScalarFilter;

class FilterGroup
{
    private array $filters = [];

    public function withFilter(FilterInterface $filter): self
    {
        $this->filters[] = $filter;

        return $this;
    }

    /**
     * @return \Traversable<int, array>
     */
    public function walkFilters(array $parameters, int $groupIndex = 0): \Traversable
    {
        if (count($this->filters) < 1) {
            return;
        }

        yield from $this->buildFilters($parameters, $groupIndex, 1, ...$this->filters);
    }

    private function buildFilters(array $parameters, int $groupIndex, int $filterIndex, FilterInterface $first, FilterInterface ...$next): \Traversable
    {
        foreach ($first as $current) {
            $childParameters = [
                ...$parameters,
                ...[
                    sprintf('searchCriteria[filterGroups][%s][filters][%s][field]', $groupIndex, $filterIndex) => $current['field'],
                    sprintf('searchCriteria[filterGroups][%s][filters][%s][value]', $groupIndex, $filterIndex) => $current['value'],
                    sprintf('searchCriteria[filterGroups][%s][filters][%s][conditionType]', $groupIndex, $filterIndex) => $current['conditionType'],
                ]
            ];

            if (count($next) >= 1) {
                yield from $this->buildFilters($childParameters, $groupIndex, $filterIndex + 1, ...$next);
            } else {
                yield $childParameters;
            }
        }
    }

    public function greaterThan(string $field, int|float|string|\DateTimeInterface $value): self
    {
        return $this->withFilter(new ScalarFilter($field, 'gt', $value));
    }

    public function lowerThan(string $field, int|float|string|\DateTimeInterface $value): self
    {
        return $this->withFilter(new ScalarFilter($field, 'lt', $value));
    }

    public function greaterThanOrEqual(string $field, int|float|string|\DateTimeInterface $value): self
    {
        return $this->withFilter(new ScalarFilter($field, 'gteq', $value));
    }

    public function lowerThanOrEqual(string $field, int|float|string|\DateTimeInterface $value): self
    {
        return $this->withFilter(new ScalarFilter($field, 'lteq', $value));
    }
}
