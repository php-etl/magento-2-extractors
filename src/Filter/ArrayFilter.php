<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2\Filter;

/**
 * @implements \IteratorAggregate<array-key,array{"field":string,"value":bool|\DateTimeInterface|float|int|string,"conditionType":string}>
 */
final class ArrayFilter implements FilterInterface, \IteratorAggregate
{
    /**
     * @param list<bool|\DateTimeInterface|float|int|string> $values
     */
    public function __construct(
        public string $field,
        public string $conditionType,
        public array $values,
        private readonly int $threshold = 200
    ) {
    }

    /**
     * @return \Traversable<int,array{"field":string,"value":string,"conditionType":string}>
     */
    public function getIterator(): \Traversable
    {
        $length = \count($this->values);
        for ($offset = 0; $offset < $length; $offset += $this->threshold) {
            yield [
                'field' => $this->field,
                'value' => implode(',', array_map(
                    fn (bool|\DateTimeInterface|float|int|string $value) => $value instanceof \DateTimeInterface ? $value->format(\DateTimeInterface::ATOM) : (string) $value,
                    \array_slice($this->values, $offset, $this->threshold, false)
                )),
                'conditionType' => $this->conditionType,
            ];
        }
    }
}
