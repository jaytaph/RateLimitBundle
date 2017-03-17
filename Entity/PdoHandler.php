<?php

namespace Noxlogic\RateLimitBundle\Entity;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class PdoHandler extends PdoSessionHandler
{

    public function __construct(\PDO $pdo)
    {

        $table = $pdo->prepare('CREATE TABLE IF NOT EXISTS database_cache (
                      id VARCHAR(128) NOT NULL PRIMARY KEY,
                      limit INTEGER NOT NULL,
                      info VARCHAR(255) NOT NULL,
                      period INTEGER NOT NULL,
                      reset INTEGER NOT NULL
                    )');

        $pdo->exec($table);

        parent::__construct($pdo, [
            'db_table' => 'database_cache',
            'db_id_col' => 'id',
            'db_data_col' => 'info',
            'db_time_col' => 'period'
        ]);
    }

    public function fetch($key){
        $this->read($key);
    }

    public function save($key, $info){
        $this->write($key, $info);
    }

    public function delete($key){
        $this->destroy($key);
    }
}