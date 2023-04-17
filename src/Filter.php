<?php

declare(strict_types=1);

namespace Kiboko\Component\Flow\Magento2;

class Filter
{
    public function __construct(
      public string $field,
      public string $conditionType,
      public string $value,
  ) {
    }
}
