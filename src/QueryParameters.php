<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

final class QueryParameters
{
    /** @var list<FilterGroup> */
    private array $groups = [];

    public function withGroup(FilterGroup $group): self
    {
        $this->groups[] = $group;

        return $this;
    }

    /**
     * @return \Traversable<int, array>
     */
    public function walkVariants(array $parameters): \Traversable
    {
        if (count($this->groups) < 1) {
            return;
        }

        yield from $this->buildFilters($parameters, 0, ...$this->groups);
    }

    private function buildFilters(array $parameters, int $groupIndex, FilterGroup $first, FilterGroup ...$next): \Traversable
    {
        foreach ($first->walkFilters($parameters, $groupIndex) as $current) {
            if (count($next) >= 1) {
                yield from $this->buildFilters($current, $groupIndex + 1, ...$next);
            } else {
                yield $current;
            }
        }
    }
}
