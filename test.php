<?php

use FpDbTest\Database;
use FpDbTest\DatabaseTest;

/**
 * @throws Exception
 */
function autoload($class)
{
    $a = array_slice(explode('\\', $class), 1);
    if (!$a) {
        throw new Exception();
    }
    $filename = implode('/', [__DIR__, ...$a]) . '.php';
    require_once $filename;
}

spl_autoload_register('autoload');

$mysqli = @new mysqli('localhost', 'root', '', 'funpay_db', 3306);
if ($mysqli->connect_errno) {
    exit($mysqli->connect_error);
}

try {
    $db = new Database($mysqli);
    $test = new DatabaseTest($db);
    $test->testBuildQuery();
} catch (Exception $e) {
    exit($e->getMessage());
}
