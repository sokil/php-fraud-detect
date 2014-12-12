<?php

namespace Sokil\FraudDetector\Collector;

class PdoMysqlCollector extends AbstractPdoCollector
{
    private $garbageCollectorCheckInterval = 1200;

    private $garbageCollectorSessionInterval = 1200;

    public function setGarbageCollector($checkInterval, $sessionInterval)
    {
        $this->garbageCollectorCheckInterval = (int) $checknterval;
        $this->garbageCollectorSessionInterval = (int) $sessionInterval;
        return $this;
    }

    public function isRateLimitExceed()
    {
        $timeNow = time();

        $query = 'SELECT 1
            FROM ' . $this->getTableName() . '
            WHERE
                `key` = :key AND
                `requestNum` <= :requestNum AND
                `expired` > :expired';

        try {
            $stmt = $this->storage->prepare($query);
            $stmt->execute(array(
                ':key' => $this->key,
                ':requestNum' => $this->requestNum,
                ':expired' => $timeNow,
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
        $timeNow = time();

        // check if record already exists and get current values
        try {
            $query = '
                SELECT requestNum, expired
                FROM ' . $this->getTableName() . '
                WHERE `key` = :key
                FOR UPDATE';

            $stmt = $this->storage->prepare($query, array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ));

            $stmt->execute(array(
                ':key' => $this->key,
            ));

            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        } catch (\PDOException $e) {
            // table yet not created
            $this->createTable();
            $this->collect();
            return;
        }

        if(!$row) {
            // record not exists
            $query = '
                INSERT INTO ' . $this->getTableName() . '(`key`, `requestNum`, `expired`)
                VALUES (:key, 1, :expired)
                ON DUPLICATE KEY UPDATE requestNum = requestNum + 1
            ';

            $stmt = $this->storage->prepare($query);
            $stmt->execute(array(
                ':key' => $this->key,
                ':expired' => $timeNow + $this->timeInterval,
            ));
        } elseif(time() < $row['expired']) {
            // in time slot - increment
            $query = '
                UPDATE ' . $this->getTableName() . '
                SET requestNum = requestNum + 1
                WHERE key = :key
            ';

            $stmt = $this->storage->prepare($query);
            $stmt->execute(array(
                ':key' => $this->key,
            ));
        } else {
            //outside time slot - set new
            $query = '
                UPDATE ' . $this->getTableName() . '
                SET
                    requestNum = 1,
                    expired = :expired
                WHERE key = :key
            ';

            $stmt = $this->storage->prepare($query);
            $stmt->execute(array(
                ':key' => $this->key,
                ':expired' => $timeNow + $this->timeInterval,
            ));
        }

        // garbage collector
        if($timeNow % $this->garbageCollectorInterval === 0) {
            $query = '
                DELETE FROM ' . $this->getTableName() . '
                WHERE expired < ' . $timeNow - $this->garbageCollectorSessionIntervale;
        }

    }
}