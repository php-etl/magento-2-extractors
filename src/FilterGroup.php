<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

class FilterGroup
{
    private array $filters = [];
    private array $longFilters = [];
    private int $offset = 0;
    private int $lenght = 200;

    public function withFilter(Filter $filter): self
    {
        $this->filters[] = [
            'field' => $filter->field,
            'value' => $filter->value,
            'condition_type' => $filter->conditionType,
        ];

        return $this;
    }

    public function withLongFilter(Filter $filter, int $offset = 0, int $lenght = 200): self
    {
        $this->longFilters[] = [
            'field' => $filter->field,
            'value' => $filter->value,
            'condition_type' => $filter->conditionType,
        ];

        $this->offset = $offset;
        $this->lenght = $lenght;

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

    private function sliceFilter(string $value): iterable
    {
        $iterator = new \ArrayIterator(explode(',', $value));
        while ($this->offset < iterator_count($iterator)) {
            $filteredValue = \array_slice(iterator_to_array($iterator), $this->offset, $this->lenght);
            $this->offset += $this->lenght;
            yield $filteredValue;
        }
    }

    public function compileLongFilters(int $groupIndex = 0)
    {
        return array_merge(...array_map(fn (array $item, int $key) => [
            sprintf('searchCriteria[filterGroups][%s][filters][%s][field]', $groupIndex, $key) => $item['field'],
            sprintf('searchCriteria[filterGroups][%s][filters][%s][value]', $groupIndex, $key) => iterator_to_array($this->sliceFilter($item['value'])),
            sprintf('searchCriteria[filterGroups][%s][filters][%s][conditionType]', $groupIndex, $key) => $item['condition_type'],
        ], $this->longFilters, array_keys($this->longFilters)));
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
