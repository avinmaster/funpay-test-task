<?php

namespace FpDbTest;

use Exception;

class DatabaseTest
{
    private DatabaseInterface $db;

    public function __construct(DatabaseInterface $db)
    {
        $this->db = $db;
    }

    /**
     * @throws Exception
     */
    public function testBuildQuery(): void
    {
        $results = [];

        $results[] = $this->db->buildQuery(
            'SELECT name FROM users WHERE user_id = 1'
        ); // 1_query

        $results[] = $this->db->buildQuery(
            'SELECT * FROM users WHERE name = ? AND block = 0',
            ['Jack']
        ); // 2_query

        $results[] = $this->db->buildQuery(
            'SELECT ?# FROM users WHERE user_id = ?d AND block = ?d',
            [['name', 'email'], 2, true]
        ); // 3_query

        $results[] = $this->db->buildQuery(
            'UPDATE users SET ?a WHERE user_id = -1',
            [['name' => 'Jack', 'email' => null]]
        ); // 4_query

        $results[] = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
            ['user_id', [1, 2, 3], $this->db->skip()]
        ); // 5_query

        $results[] = $this->db->buildQuery(
            'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
            ['user_id', [1, 2, 3], true]
        ); // 6_query

        // Не нравится мне этот блок
//        foreach ([null, true] as $block) {
//            $results[] = $this->db->buildQuery(
//                'SELECT name FROM users WHERE ?# IN (?a){ AND block = ?d}',
//                ['user_id', [1, 2, 3], $block ?? $this->db->skip()]
//            ); // 5_query and 6_query
//        }

        $correct = [
            'SELECT name FROM users WHERE user_id = 1',
            'SELECT * FROM users WHERE name = \'Jack\' AND block = 0',
            'SELECT `name`, `email` FROM users WHERE user_id = 2 AND block = 1',
            'UPDATE users SET `name` = \'Jack\', `email` = NULL WHERE user_id = -1',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3)',
            'SELECT name FROM users WHERE `user_id` IN (1, 2, 3) AND block = 1',
        ];

        $hasError = false;

        // Выводим результаты
        foreach ($results as $key => $result) {
            echo 'Test ' . ($key + 1) . ' (';
            if ($result !== $correct[$key]) {
                $hasError = true;

                echo "\033[31mfailure\033[0m";
            } else {
                echo "\033[32msuccess\033[0m";
            }
            echo '):' . PHP_EOL;
            echo 'Result: ' . $result . PHP_EOL;
            echo 'Correct: ' . $correct[$key] . PHP_EOL . PHP_EOL;
        }

        if ($hasError) {
            throw new Exception('Test failed');
        }
    }
}
