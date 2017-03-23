<?php

namespace Noxlogic\RateLimitBundle\Entity;

use Symfony\Component\HttpFoundation\Session\Storage\Handler\PdoSessionHandler;

class PdoHandler extends PdoSessionHandler
{
    /**
     * No locking is done. This means sessions are prone to loss of data due to
     * race conditions of concurrent requests to the same session. The last session
     * write will win in this case. It might be useful when you implement your own
     * logic to deal with this like an optimistic approach.
     */
    const LOCK_NONE = 0;

    /**
     * Creates an application-level lock on a session. The disadvantage is that the
     * lock is not enforced by the database and thus other, unaware parts of the
     * application could still concurrently modify the session. The advantage is it
     * does not require a transaction.
     * This mode is not available for SQLite and not yet implemented for oci and sqlsrv.
     */
    const LOCK_ADVISORY = 1;

    /**
     * Issues a real row lock. Since it uses a transaction between opening and
     * closing a session, you have to be careful when you use same database connection
     * that you also use for your application logic. This mode is the default because
     * it's the only reliable solution across DBMSs.
     */
    const LOCK_TRANSACTIONAL = 2;

    /**
     * @var \PDO|null PDO instance or null when not connected yet
     */
    private $pdo;

    /**
     * @var string|null|false DSN string or null for session.save_path or false when lazy connection disabled
     */
    private $dsn = false;

    /**
     * @var string Database driver
     */
    private $driver;

    /**
     * @var string Table name
     */
    private $table;

    /**
     * @var string Column for cache id
     */
    private $idCol;

    /**
     * @var string Column for cache data
     */
    private $dataCol;

    /**
     * @var string Column for lifetime
     */
    private $lifetimeCol;

    /**
     * @var string Column for timestamp
     */
    private $timeCol;

    /**
     * @var string Username when lazy-connect
     */
    private $username = '';

    /**
     * @var string Password when lazy-connect
     */
    private $password = '';

    /**
     * @var array Connection options when lazy-connect
     */
    private $connectionOptions = array();

    /**
     * @var int The strategy for locking, see constants
     */
    private $lockMode = self::LOCK_TRANSACTIONAL;

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
        $this->getConnection();

        switch ($this->driver) {
            case 'pgsql':
                $sql = "CREATE TABLE IF NOT EXISTS database_cache ( id VARCHAR(128) NOT NULL PRIMARY KEY, limit INTEGER NOT NULL, info VARCHAR(255) NOT NULL, period INTEGER NOT NULL, reset INTEGER NOT NULL )";
                break;
            default:
                throw new \DomainException(sprintf('Creating the database cache table is currently not implemented for PDO driver "%s".', $this->driver));
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