<?php

namespace FpDbTest\src\Components;

use Exception;
use FpDbTest\src\Enums\ArgumentSpecifier;
use FpDbTest\src\Interfaces\EscaperInterface;
use FpDbTest\src\Interfaces\FormatterInterface;
use FpDbTest\src\Utilities\Skip;

readonly class Formatter implements FormatterInterface
{
    public function __construct(private EscaperInterface $escaper)
    {
    }

    /**
     * Преобразует параметр для использования в запросе
     * @param mixed $value - значение параметра
     * @param string|null $specifier - спецификатор типа параметра (self::SPECIFIER_*)
     * @return float|int|string
     * @throws Exception
     */
    public function formatQuery(mixed $value, ?string $specifier = null): float|int|string
    {
        if ($value instanceof Skip) {
            return '';
        }

        return match ($specifier) {
            ArgumentSpecifier::INTEGER->value => (int)$value,
            ArgumentSpecifier::FLOAT->value => (float)$value,
            ArgumentSpecifier::ARRAY->value => $this->formatArray($value),
            ArgumentSpecifier::IDENTIFIER->value => $this->formatIdentifier($value),
            default => $this->escaper->escapeValue($value),
        };
    }

    /**
     * Форматирует массив для использования в запросе, различая ассоциативные и не ассоциативные массивы.
     * @param array $value
     * @return string
     * @throws Exception
     */
    private function formatArray(array $value): string
    {
        if ($this->isAssociativeArray($value)) {
            return $this->formatAssociativeArray($value);
        } else {
            return $this->formatNonAssociativeArray($value);
        }
    }

    /**
     * Проверяет, является ли массив ассоциативным
     * @param array $array
     * @return bool
     */
    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Форматирует ассоциативный массив для использования в запросе
     * @param array $array
     * @return string
     * @throws Exception
     */
    private function formatAssociativeArray(array $array): string
    {
        return implode(', ', array_map(function ($k, $v) {
            return $this->escaper->escapeIdentifier($k) . ' = ' . $this->escaper->escapeValue($v);
        }, array_keys($array), $array));
    }

    /**
     * Форматирует не ассоциативный массив для использования в запросе
     * @param array $array
     * @return string
     * @throws Exception
     */
    private function formatNonAssociativeArray(array $array): string
    {
        return implode(', ', array_map([$this->escaper, 'escapeValue'], $array));
    }

    /**
     * Форматирует идентификатор для использования в запросе
     * @param mixed $value
     * @return string
     */
    private function formatIdentifier(mixed $value): string
    {
        if (is_array($value)) {
            return implode(', ', array_map([$this->escaper, 'escapeIdentifier'], $value));
        } else {
            return $this->escaper->escapeIdentifier((string)$value);
        }
    }
}
