<?php

namespace FpDbTest\src\Components;

use Exception;
use FpDbTest\src\Enums\ArgumentSpecifier;
use FpDbTest\src\Enums\BlockDelimiterSpecifier;
use FpDbTest\src\Interfaces\FormatterInterface;
use FpDbTest\src\Interfaces\QueryBuilderInterface;
use FpDbTest\src\Utilities\Skip;

readonly class QueryBuilder implements QueryBuilderInterface
{
    public function __construct(private FormatterInterface $formatter)
    {
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
        return explode(ArgumentSpecifier::MIXED->value, $query);
    }

    /**
     * Вставляет параметры в части запроса, форматируя их в соответствии со спецификаторами.
     * @param array $parts
     * @param array $args
     * @return array
     * @throws Exception
     */
    private function insertParametersIntoQuery(array $parts, array $args): array
    {
        $result = [$parts[0]];

        foreach ($args as $i => $arg) {
            $specifier = null;
            if (isset($parts[$i + 1][0]) && in_array($parts[$i + 1][0], ArgumentSpecifier::getSpecifiers())) {
                $specifier = $parts[$i + 1][0];
                $parts[$i + 1] = substr($parts[$i + 1], 1);
            }
            $result[] = $this->formatter->formatQuery($arg, $specifier);
            $result[] = $parts[$i + 1];
        }

        return $result;
    }

    /**
     * Проверяет наличие аргумента Skip в массиве аргументов
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
     * Удаляет блоки из запроса
     * @param string $query
     * @return string
     */
    private function removeBlocksFromQuery(string $query): string
    {
        return preg_replace(
            '/[' . BlockDelimiterSpecifier::OPENING->value . '].*[' . BlockDelimiterSpecifier::CLOSING->value . ']/',
            '',
            $query
        );
    }

    /**
     * Удаляет символы блоков из запроса
     * @param string $query
     * @return string
     */
    private function removeBlockDelimitersFromQuery(string $query): string
    {
        return preg_replace(
            '/[' . BlockDelimiterSpecifier::OPENING->value . BlockDelimiterSpecifier::CLOSING->value . ']/',
            '',
            $query
        );
    }
}
