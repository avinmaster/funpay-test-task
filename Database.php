<?php

namespace FpDbTest;

use Exception;
use mysqli;

class Database implements DatabaseInterface
{
    private mysqli $mysqli;

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
    private Skip $skipClass;

    public function __construct(mysqli $mysqli)
    {
        $this->mysqli = $mysqli;
        $this->skipClass = new Skip();
    }

    /**
     * Преобразует значение для использования в запросе
     * @param mixed $value
     * @return string
     * @throws Exception
     */
    public function escapeValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        } elseif (is_string($value)) {
            return "'" . $this->mysqli->escape_string($value) . "'"; // пример: 'Jack'
        } elseif (is_float($value) || is_int($value)) {
            return $value; // пример: 1.5, 1
        } elseif (is_bool($value)) {
            return $value ? 1 : 0; // пример: 1, 0
        } else {
            throw new Exception('Unsupported parameter type');
        }
    }

    /**
     * Преобразует идентификатор для использования в запросе
     * @param mixed $value
     * @return string
     */
    public function escapeIdentifier($value): string
    {
        return '`' . str_replace('`', '``', $value) . '`'; // пример: `user_id`
    }

    /**
     * Возвращает специальное значение. Если это значение будет передано в качестве параметра в метод buildQuery,
     * то блок "{...}" не попадает в сформированный запрос.
     * @return Skip
     */
    public function skip(): Skip
    {
        return $this->skipClass;
    }

    /**
     * Преобразует параметр для использования в запросе
     * @param mixed $value - значение параметра
     * @param string|null $specifier - спецификатор типа параметра (self::SPECIFIER_*)
     * @return float|int|string
     * @throws Exception
     */
    public function formatQuery($value, string $specifier = null)
    {
        switch ($specifier) {
            case self::SPECIFIER_INTEGER:
                return (int)$value;
            case self::SPECIFIER_FLOAT:
                return (float)$value;
            case self::SPECIFIER_ARRAY:
                if (!is_array($value)) throw new Exception('Parameter with specifier "a" must be an array');

                // Если массив ассоциативный, то преобразуем его в строку вида "key1 = value1, key2 = value2"
                if (array_keys($value) === range(0, count($value) - 1)) {
                    return implode(', ', array_map(function ($v) {
                        return $this->escapeValue($v);
                    }, $value));
                } else { // иначе, обрабатываем как массив со значениями
                    $pairs = [];
                    foreach ($value as $k => $v) {
                        $pairs[] = $this->escapeIdentifier($k) . ' = ' . $this->escapeValue($v);
                    }
                    return implode(', ', $pairs);
                }
            case self::SPECIFIER_IDENTIFIER:
                // Если значение - массив, то преобразуем его в строку вида "value1, value2"
                if (is_array($value)) {
                    return implode(', ', array_map(function ($v) {
                        return $this->escapeIdentifier((string)$v);
                    }, $value));
                } else { // иначе, обрабатываем как строку
                    return $this->escapeIdentifier((string)$value);
                }
            default:
                return $this->escapeValue($value);
        }
    }

    /**
     * Собирает запрос из шаблона запроса и параметров.
     * Нужно обязательно соблюдать порядок следования параметров в запросе.
     * @param string $query - запрос с плейсхолдерами вида "?", "?d", "?f", "?a", "?#" (виды по умолчанию)
     * @param array $args - массив параметров для подстановки в запрос
     * @return string - собранный запрос
     * @throws Exception
     */
    public function buildQuery(string $query, array $args = []): string
    {
        $parts = explode(self::ARG_SYMBOL, $query);
        $result = [$parts[0]];

        $isSkipArgExists = false;
        foreach ($args as $i => $arg) {
            $isSkipArgExists = $isSkipArgExists || $arg instanceof Skip;

            $specifier = null;
            if (isset($parts[$i + 1][0]) && in_array($parts[$i + 1][0], self::SPECIFIERS)) {
                $specifier = $parts[$i + 1][0];
                $parts[$i + 1] = substr($parts[$i + 1], 1);
            }
            $result[] = $this->formatQuery($arg, $specifier);
            $result[] = $parts[$i + 1];
        }

        // Перед тем, как начать работу над фигурными скобками поискал и нашел инфу по синтаксису с их использованием. Это не очень хорошо работает:
        // https://dev.mysql.com/doc/refman/8.0/en/expressions.html
        // Но да, это используется редко.
        $wholeResult = implode('', $result);
        if ($isSkipArgExists) {
            // Удаляем блоки вида "{...}"
            $wholeResult = preg_replace(
                '/[' . self::OPENING_BLOCK_DELIMITER . '].*[' . self::CLOSING_BLOCK_DELIMITER . ']/',
                '',
                $wholeResult
            );
        } else {
            // Удаляем символы вида "{", "}" и оставляем содержимое блоков
            $wholeResult = preg_replace(
                '/[' . self::OPENING_BLOCK_DELIMITER . self::CLOSING_BLOCK_DELIMITER . ']/',
                '',
                $wholeResult
            );
        }

        return $wholeResult;
    }
}
