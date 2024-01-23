<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2\Filter;

final class ArrayFilter implements FilterInterface, \IteratorAggregate
{
    public function __construct(
        public string $field,
        public string $conditionType,
        public array $value,
        private readonly int $threshold = 200
    ) {
    }

    /**
     * @return \Traversable<int, {field: string, value: string, conditionType: string}>
     */
    public function getIterator(): \Traversable
    {
        $length = \count($this->value);
        for ($offset = 0; $offset < $length; $offset += $this->threshold) {
            yield [
                'field' => $this->field,
                'value' => implode(',', \array_slice($this->value, $offset, $this->threshold, false)),
                'conditionType' => $this->conditionType,
            ];
        }
    }
}
