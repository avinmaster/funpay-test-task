<?php

namespace FpDbTest\src;

use Exception;
use FpDbTest\src\Interfaces\DatabaseInterface;
use FpDbTest\src\Interfaces\QueryBuilderInterface;
use FpDbTest\src\Components\Escaper;
use FpDbTest\src\Components\Formatter;
use FpDbTest\src\Components\QueryBuilder;
use FpDbTest\src\Utilities\Skip;
use mysqli;

readonly class Database implements DatabaseInterface
{
    private QueryBuilderInterface $queryBuilder;
    private Skip $skipClass;

    public function __construct(mysqli $mysqli)
    {
        $escaper = new Escaper($mysqli);
        $formatter = new Formatter($escaper);
        $this->queryBuilder = new QueryBuilder($formatter);
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
        return $this->queryBuilder->buildQuery($query, $args);
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
