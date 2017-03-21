<?php

namespace Noxlogic\RateLimitBundle\Entity;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class PdoHandler extends PdoSessionHandler
{
    public function __construct($pdoOrDsn = null, array $options = array())
    {
        if ($pdoOrDsn instanceof \PDO) {
            if (\PDO::ERRMODE_EXCEPTION !== $pdoOrDsn->getAttribute(\PDO::ATTR_ERRMODE)) {
                throw new \InvalidArgumentException(sprintf('"%s" requires PDO error mode attribute be set to throw Exceptions (i.e. $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION))', __CLASS__));
            }

            $this->pdo = $pdoOrDsn;
            $this->driver = $this->pdo->getAttribute(\PDO::ATTR_DRIVER_NAME);
        } else {
            $this->dsn = $pdoOrDsn;
        }

        $this->table = isset($options['db_table']) ? $options['db_table'] : $this->table;
        $this->idCol = isset($options['db_id_col']) ? $options['db_id_col'] : $this->idCol;
        $this->dataCol = isset($options['db_data_col']) ? $options['db_data_col'] : $this->dataCol;
        $this->lifetimeCol = isset($options['db_lifetime_col']) ? $options['db_lifetime_col'] : $this->lifetimeCol;
        $this->timeCol = isset($options['db_time_col']) ? $options['db_time_col'] : $this->timeCol;
        $this->username = isset($options['db_username']) ? $options['db_username'] : $this->username;
        $this->password = isset($options['db_password']) ? $options['db_password'] : $this->password;
        $this->connectionOptions = isset($options['db_connection_options']) ? $options['db_connection_options'] : $this->connectionOptions;
        $this->lockMode = isset($options['lock_mode']) ? $options['lock_mode'] : $this->lockMode;
    }

    public function createTable()
    {
        // connect if we are not yet
        $this->getConnection();

        switch ($this->driver) {
            case 'pgsql':
                $sql = "CREATE TABLE IF NOT EXISTS database_cache ( id VARCHAR(128) NOT NULL PRIMARY KEY, limit INTEGER NOT NULL, info VARCHAR(255) NOT NULL, period INTEGER NOT NULL, reset INTEGER NOT NULL )";
                break;
            default:
                throw new \DomainException(sprintf('Creating the session table is currently not implemented for PDO driver "%s".', $this->driver));
        }

        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }
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