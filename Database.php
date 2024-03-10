<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    public const ARG_SYMBOL = '?';
    public const SPECIFIER_INTEGER = 'd';
    public const SPECIFIER_FLOAT = 'f';
    public const SPECIFIER_ARRAY = 'a';
    public const SPECIFIER_IDENTIFIER = '#';
    public const SPECIFIERS = [
        self::SPECIFIER_INTEGER,
        self::SPECIFIER_FLOAT,
        self::SPECIFIER_ARRAY,
        self::SPECIFIER_IDENTIFIER,
    ];
    public const OPENING_BLOCK_DELIMITER = '{';
    public const CLOSING_BLOCK_DELIMITER = '}';
    /**
     * @var mysqli
     */
    private mysqli $mysqli;
    /**
     * @var Skip
     */
    private Skip $skipClass;

    /**
     * @param mysqli $mysqli
     */
    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->skipClass = new Skip();
    }

    /**
     * Собирает запрос из шаблона запроса и параметров
     * Нужно обязательно соблюдать порядок следования параметров в запросе
     * @param string $query - запрос с плейсхолдерами вида "?", "?d", "?f", "?a", "?#" (виды по умолчанию)
     * @param array $args - массив параметров для подстановки в запрос
     * @return string - собранный запрос
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string
    {
        $parts = $this->splitQueryIntoParts($query);
        $result = $this->insertParametersIntoQuery($parts, $args);
        $wholeResult = implode('', $result);

        if ($this->isSkipArgExists($args)) {
            $wholeResult = $this->removeBlocksFromQuery($wholeResult);
        } else {
            $wholeResult = $this->removeBlockDelimitersFromQuery($wholeResult);
        }

        return $wholeResult;
    }

    /**
     * @param string $query
     * @return array
     */
    private function splitQueryIntoParts(string $query): array
    {
        return explode(self::ARG_SYMBOL, $query);
    }

    /**
     * @throws Exception
     */
    private function insertParametersIntoQuery(array $parts, array $args): array
    {
        $result = [$parts[0]];

        foreach ($args as $i => $arg) {
            $specifier = null;
            if (isset($parts[$i + 1][0]) && in_array($parts[$i + 1][0], self::SPECIFIERS)) {
                $specifier = $parts[$i + 1][0];
                $parts[$i + 1] = substr($parts[$i + 1], 1);
            }
            $result[] = $this->formatQuery($arg, $specifier);
            $result[] = $parts[$i + 1];
        }

        return $result;
    }

    /**
     * Преобразует параметр для использования в запросе
     * @param mixed $value - значение параметра
     * @param string|null $specifier - спецификатор типа параметра (self::SPECIFIER_*)
     * @return float|int|string
     * @throws Exception
     */
    private function formatQuery(mixed $value, string $specifier = null): float|int|string
    {
        return match ($specifier) {
            self::SPECIFIER_INTEGER => (int)$value,
            self::SPECIFIER_FLOAT => (float)$value,
            self::SPECIFIER_ARRAY => $this->formatArray($value),
            self::SPECIFIER_IDENTIFIER => $this->formatIdentifier($value),
            default => $this->escapeValue($value),
        };
    }

    /**
     * Форматирует массив для использования в запросе.
     *
     * Если массив ассоциативный, он преобразуется в строку пар ключ-значение, разделенных запятыми.
     * Если массив не ассоциативный, он преобразуется в строку значений, разделенных запятыми.
     *
     * @param array $value The array to format.
     * @return string The formatted array.
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
     * Проверяет, является ли массив ассоциативным.
     *
     * @param array $array The array to check.
     * @return bool True if the array is associative, false otherwise.
     */
    private function isAssociativeArray(array $array): bool
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Форматирует ассоциативный массив для использования в запросе.
     *
     * Массив преобразуется в строку пар ключ-значение, разделенных запятыми.
     *
     * @param array $array The associative array to format.
     * @return string The formatted array.
     * @throws Exception
     */
    private function formatAssociativeArray(array $array): string
    {
        return implode(', ', array_map(function ($k, $v) {
            return $this->escapeIdentifier($k) . ' = ' . $this->escapeValue($v);
        }, array_keys($array), $array));
    }

    /**
     * Преобразует идентификатор для использования в запросе
     * @param string $value
     * @return string
     */
    private function escapeIdentifier(string $value): string
    {
        return '`' . str_replace('`', '``', $value) . '`'; // пример: `user_id`
    }

    /**
     * Преобразует значение для использования в запросе
     * @param mixed $value
     * @return string
     * @throws Exception
     */
    private function escapeValue(mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        } elseif (is_string($value)) {
            return "'" . $this->mysqli->escape_string($value) . "'";
        } elseif (is_float($value) || is_int($value)) {
            return (string)$value;
        } elseif (is_bool($value)) {
            return (string)(int)$value;
        } else {
            throw new Exception('Unsupported parameter type');
        }
    }

    /**
     * Форматирует не ассоциативный массив для использования в запросе.
     *
     * Массив преобразуется в строку значений, разделенных запятыми.
     *
     * @param array $array The non-associative array to format.
     * @return string The formatted array.
     */
    private function formatNonAssociativeArray(array $array): string
    {
        return implode(', ', array_map([$this, 'escapeValue'], $array));
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function formatIdentifier(mixed $value): string
    {
        // Если значение - массив, то преобразуем его в строку вида "value1, value2"
        if (is_array($value)) {
            return implode(', ', array_map(function ($v) {
                return $this->escapeIdentifier((string)$v);
            }, $value));
        } else { // иначе, обрабатываем как строку
            return $this->escapeIdentifier((string)$value);
        }
    }

    /**
     * @param array $args
     * @return bool
     */
    private function isSkipArgExists(array $args): bool
    {
        foreach ($args as $arg) {
            if ($arg instanceof Skip) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param string $query
     * @return string
     */
    private function removeBlocksFromQuery(string $query): string
    {
        return preg_replace(
            '/[' . self::OPENING_BLOCK_DELIMITER . '].*[' . self::CLOSING_BLOCK_DELIMITER . ']/',
            '',
            $query
        );
    }

    /**
     * @param string $query
     * @return string
     */
    private function removeBlockDelimitersFromQuery(string $query): string
    {
        return preg_replace(
            '/[' . self::OPENING_BLOCK_DELIMITER . self::CLOSING_BLOCK_DELIMITER . ']/',
            '',
            $query
        );
    }

    /**
     * Возвращает специальное значение. Если это значение будет передано в качестве параметра в метод buildQuery,
     * то блок "{...}" не попадает в сформированный запрос
     * @return Skip
     */
    public function skip(): Skip
    {
        return $this->skipClass;
    }
}
