<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2\Filter;

final class ScalarFilter implements FilterInterface, \IteratorAggregate
{
    public function __construct(
        public string $field,
        public string $conditionType,
        public bool|int|float|string|\DateTimeInterface $value,
    ) {}

    public function getIterator(): \Traversable
    {
        yield [
            'field' => $this->field,
            'value' => $this->value,
            'conditionType' => $this->conditionType,
        ];
    }
}
