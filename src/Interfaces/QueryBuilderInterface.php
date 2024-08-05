<?php

namespace FpDbTest\src\Interfaces;

use Exception;

interface QueryBuilderInterface
{
    /**
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string;
}
