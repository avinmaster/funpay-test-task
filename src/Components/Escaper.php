<?php

namespace FpDbTest\src\Components;

use Exception;
use FpDbTest\src\Interfaces\EscaperInterface;
use mysqli;

readonly class Escaper implements EscaperInterface
{
    public function __construct(private mysqli $mysqli)
    {
    }

    /**
     * Преобразует идентификатор для использования в запросе
     * @param string $value
     * @return string
     */
    public function escapeIdentifier(string $value): string
    {
        return '`' . str_replace('`', '``', $value) . '`';
    }

    /**
     * Преобразует значение для использования в запросе
     * @param mixed $value
     * @return string
     * @throws Exception
     */
    public function escapeValue(mixed $value): string
    {
        return match (true) {
            $value === null => 'NULL',
            is_string($value) => "'" . $this->mysqli->escape_string($value) . "'",
            is_float($value), is_int($value) => (string)$value,
            is_bool($value) => (string)(int)$value,
            default => throw new Exception('Unsupported parameter type'),
        };
    }
}
