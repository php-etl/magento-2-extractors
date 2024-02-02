<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2\Filter;

/**
 * @implements \IteratorAggregate<array-key,array{"field":string,"value":bool|\DateTimeInterface|float|int|string,"conditionType":string}>
 */
final class ScalarFilter implements FilterInterface, \IteratorAggregate
{
    public function __construct(
        public string $field,
        public string $conditionType,
        public bool|\DateTimeInterface|float|int|string $value,
    ) {
    }

    /**
     * @return \Traversable<int,array{"field":string,"value":string,"conditionType":string}>
     */
    public function getIterator(): \Traversable
    {
        yield [
            'field' => $this->field,
            'value' => $this->value instanceof \DateTimeInterface ? $this->value->format(\DateTimeInterface::ATOM) : (string) $this->value,
            'conditionType' => $this->conditionType,
        ];
    }
}
