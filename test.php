<?php

use FpDbTest\src\Database;
use FpDbTest\src\Tests\DatabaseTest;

spl_autoload_register(
    /**
     * Автозагрузка классов.
     *
     * @param string $class
     * @throws Exception
     */
    function (string $class): void {
        $pathParts = array_slice(explode('\\', $class), 1);
        if (empty($pathParts)) {
            throw new Exception('Class path is empty: ' . $class);
        }
        $filename = implode(DIRECTORY_SEPARATOR, [__DIR__, ...$pathParts]) . '.php';
        require_once $filename;
    }
);


/**
 * Настройки подключения к базе данных.
 *
 * @return mysqli
 * @throws Exception
 */
function getMysqliConnection(): mysqli
{
    $mysqli = new mysqli('localhost', 'root', '', 'funpay_db', 3306);
    if ($mysqli->connect_errno) {
        throw new Exception($mysqli->connect_error);
    }
    return $mysqli;
}


/**
 * Запуск теста метода buildQuery.
 *
 * @throws Exception
 */
function runTests(): void
{
    $mysqli = getMysqliConnection();
    $db = new Database($mysqli);
    $test = new DatabaseTest($db);
    $test->testBuildQuery();
}

try {
    runTests();
} catch (Exception $e) {
    exit(
        $e->getMessage()
    );
}
