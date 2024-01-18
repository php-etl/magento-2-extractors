<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2\Filter;

/**
 * @extends \Traversable<int, {field: string, value: string, conditionType: string}>
 */
interface FilterInterface extends \Traversable
{
}
