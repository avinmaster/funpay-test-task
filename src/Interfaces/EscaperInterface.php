<?php

namespace FpDbTest\src\Interfaces;

use Exception;

interface EscaperInterface
{
    public function escapeIdentifier(string $value): string;

    /**
     * @throws Exception
     */
    public function escapeValue(mixed $value): string;
}
