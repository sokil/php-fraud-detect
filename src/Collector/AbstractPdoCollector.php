<?php

namespace Sokil\FraudDetector\Collector;

use \Sokil\FraudDetector\AbstractCollector;

/**
 * @property \PDO $storage Instance of \PDO storage
 */
abstract class AbstractPdoCollector extends AbstractCollector
{
    private $tableName;

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