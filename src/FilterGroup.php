<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

class FilterGroup
{
    private array $filters = [];

    public function withFilter(Filter $filter): self
    {
        $this->filters[] = [
            'field' => $filter->field,
            'value' => $filter->value,
            'condition_type' => $filter->conditionType,
        ];

        return $this;
    }

    public function withFilters(Filter ...$filters): self
    {
        array_walk($filters, fn (Filter $filter) => $this->filters[] = [
            'field' => $filter->field,
            'value' => $filter->value,
            'condition_type' => $filter->conditionType,
        ]);

        return $this;
    }

    public function compileFilters(int $groupIndex = 0): array
    {
        return array_merge(...array_map(fn (array $item, int $key) => [
            sprintf('searchCriteria[filterGroups][%s][filters][%s][field]', $groupIndex, $key) => $item['field'],
            sprintf('searchCriteria[filterGroups][%s][filters][%s][value]', $groupIndex, $key) => $item['value'],
            sprintf('searchCriteria[filterGroups][%s][filters][%s][conditionType]', $groupIndex, $key) => $item['condition_type'],
        ], $this->filters, array_keys($this->filters)));
    }

    public function greaterThan(string $field, mixed $value): self
    {
        return $this->withFilter(new Filter($field, 'gt', $value));
    }

    public function greaterThanEqual(string $field, mixed $value): self
    {
        return $this->withFilter(new Filter($field, 'gteq', $value));
    }
}
