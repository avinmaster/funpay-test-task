<?php

namespace FpDbTest\src\Interfaces;

use Exception;

interface FormatterInterface
{
    /**
     * @throws Exception
     */
    public function formatQuery(mixed $value, ?string $specifier = null): float|int|string;
}
