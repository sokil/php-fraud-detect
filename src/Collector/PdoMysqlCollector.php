<?php

namespace Sokil\FraudDetector\Collector;

class PdoMysqlCollector extends AbstractPdoCollector
{
    private $garbageCollectorCheckInterval = 1200;

    private $garbageCollectorSessionInterval = 1200;

    public function setGarbageCollector($checkInterval, $sessionInterval)
    {
        $this->garbageCollectorCheckInterval = (int) $checkInterval;
        $this->garbageCollectorSessionInterval = (int) $sessionInterval;
        return $this;
    }

    public function isRateLimitExceed()
    {
        $timeNow = microtime(true);

        $query = 'SELECT 1
            FROM ' . $this->getTableName() . '
            WHERE
                `key` = :key AND
                `expired` >= :timeNow AND
                `requestNum` >= :maxRequestNum';

        $parameters = array(
            ':key' => $this->key,
            ':maxRequestNum' => $this->requestNum,
            ':timeNow' => $timeNow,
        );

        try {
            $stmt = $this->storage->prepare($query, array(
                \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION
            ));

            $stmt->execute($parameters);

        } catch (\PDOException $e) {
            // if exception occurs, than no table still created
            return false;
        }

        // if record found than key has exceed requests
        if(!$stmt->rowCount()) {
            return false;
        }

        return true;
    }

    private function createTable()
    {
        $this->storage->query('
            CREATE TABLE ' . $this->getTableName() . '(
                `key` varchar(255) PRIMARY KEY NOT NULL,
                `requestNum` int NOT NULL DEFAULT 0,
                `expired` numeric(13, 3)
            ) ENGINE=Memory CHARSET=utf8;
        ');

    }

    public function collect()
    {
        $timeNow = microtime(true);

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
            $stmt->bindValue(':key', $this->key);
            $stmt->bindValue(':expired', $timeNow + $this->timeInterval);
            $stmt->execute();

            return;

        }

        $expiredTimestamp = (float) $row['expired'];

        if($timeNow <= $expiredTimestamp) {
            // in time slot - increment
            $query = '
                UPDATE ' . $this->getTableName() . '
                SET requestNum = requestNum + 1
                WHERE `key` = :key
            ';
            $parameters = array(
                ':key' => $this->key,
            );
        } else {
            //outside time slot - set new
            $query = '
                UPDATE ' . $this->getTableName() . '
                SET
                    requestNum = 1,
                    expired = :expired
                WHERE `key` = :key
            ';
            $parameters = array(
                ':key' => $this->key,
                ':expired' => $timeNow + $this->timeInterval,
            );
        }

        $stmt = $this->storage->prepare($query);
        $stmt->execute($parameters);


        // garbage collector
        if($timeNow % $this->garbageCollectorCheckInterval === 0) {
            $query = '
                DELETE FROM ' . $this->getTableName() . '
                WHERE expired < ' . date('Y-m-d H:i:s', $timeNow - $this->garbageCollectorSessionIntervale);
        }

    }
}