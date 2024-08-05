<?php

namespace FpDbTest\src\Tests;

use Exception;
use FpDbTest\src\Interfaces\DatabaseInterface;

class DatabaseTest
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * Не нравится мне старый код)
     * @throws Exception
     */
    public function testBuildQuery(array $customTestCases = []): void
    {
        $testCases = [
            [
                'query' => 'SELECT name FROM users WHERE user_id = 1',
                'args' => [],
                'expected' => 'SELECT name FROM users WHERE user_id = 1'
            ],
            [
                'query' => 'SELECT * FROM users WHERE name = ? AND block = 0',
                'args' => ['Jack'],
                'expected' => 'SELECT * FROM users WHERE name = \'Jack\' AND block = 0'
            ],
            [
                'query' => 'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
                'args' => [['name', 'email'], 2, true],
                'expected' => 'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1'
            ],
            [
                'query' => 'UPDATE users SET ?a WHERE user_id = -1',
                'args' => [['name' => 'Jack', 'email' => null]],
                'expected' => 'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1'
            ],
            [
                'query' => 'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                'args' => ['user_id', [1, 2, 3], $this->db->skip()],
                'expected' => 'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)'
            ],
            [
                'query' => 'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
                'args' => ['user_id', [1, 2, 3], true],
                'expected' => 'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1'
            ]
        ];

        if (!empty($customTestCases)) {
            // первыми идут кастомные тесты
            $testCases = array_merge($customTestCases, $testCases);
        }

        $hasError = false;
        $testCasesCount = count($testCases);
        $failedTestsCount = 0;

        foreach ($testCases as $key => $testCase) {
            $result = $this->db->buildQuery($testCase['query'], $testCase['args']);
            $expected = $testCase['expected'];
            $next = $key + 1;

            echo "Test $next (";
            if ($result !== $expected) {
                $hasError = true;
                $failedTestsCount += 1;
                echo "\033[31mfailure\033[0m";
            } else {
                echo "\033[32msuccess\033[0m";
            }
            echo '):' . PHP_EOL;
            echo "Result: $result" . PHP_EOL;
            echo "Expected: $expected" . PHP_EOL . PHP_EOL;
        }

        if ($hasError) {
            throw new Exception("Failed $failedTestsCount/$testCasesCount tests");
        }

        echo "\033[32m". "All tests passed ($testCasesCount/$testCasesCount)" . "\033[0m" . PHP_EOL;
    }
}
