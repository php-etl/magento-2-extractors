<?php

namespace Kiboko\Component\Flow\Magento2;

class FilterGroup
{
    private array $filters = [];

    public function asArray(): array
    {
        return $this->filters;
    }

    public function withFilter(string $field, string $operator, mixed $value): self
    {
        $this->filters = [
            '[filters][0][field]' => $field,
            '[filters][0][value]' => $value,
            '[filters][0][condition_type]' => $operator,
        ];

        return $this;
    }

    public function greaterThan(string $field, mixed $value): self
    {
        return $this->withFilter($field, 'gt', $value);
    }

    public function greaterThanEqual(string $field, mixed $value): self
    {
        return $this->withFilter($field, 'gteq', $value);
    }
}
