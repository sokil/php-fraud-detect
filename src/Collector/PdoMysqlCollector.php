<?php

namespace Sokil\FraudDetector\Collector;

class PdoMysqlCollector extends AbstractPdoCollector
{
    public function isRateLimitExceed()
    {
        $query = 'SELECT 1
            FROM ' . $this->getTableName() . '
            WHERE
                `key` = ? AND
                `requestNum` <= ? AND
                `expired` > NOW()';

        try {
            $stmt = $this->storage->prepare($query);
            $stmt->execute(array(
                $this->key,
                $this->requestNum,
            ));
        } catch (\PDOException $e) {
            return false;
        }

        if($stmt->fetchColumn()) {
            return false;
        }

        return true;
    }

    private function createTable()
    {
        $this->storage->query('
            CREATE TABLE ' . $this->getTableName() . '(
                `key` varchar(255) PRIMARY KEY NOT NULL,
                `requestNum` int,
                `expired` timestamp
            ) ENGINE=Memory CHARSET=utf8;
        ');
    }

    public function collect()
    {
        $query = 'INSERT INTO ' . $this->getTableName() . '(`key`, `requestNum`, `expired`)
            VALUES (?, 1, ?)
            ON DUPLICATE KEY UPDATE requestNum = requestNum + 1';

        try {
            $stmt = $this->storage->prepare($query);
            $stmt->execute(array(
                $this->key,
                time() + $this->timeInterval,
            ));
        } catch (\PDOException $e) {
            echo $e->getMessage();
            // table yet not created
            $this->createTable();
            $this->collect();
        }
    }
}