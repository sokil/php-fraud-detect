<?php

namespace Sokil\FraudDetector\Collector;

/**
 * @property \PDO $pdo Instance of \PDO storage
 */
abstract class AbstractPdoCollector extends AbstractCollector
{
    protected $pdo;

    private $tableName;

    public function setPdo(\PDO $pdo)
    {
        $this->pdo = $pdo;
        return $this;
    }

    public function setTableName($name)
    {
        $this->tableName = $name;
        return $this;
    }

    public function getTableName()
    {
        return $this->tableName;
    }
}