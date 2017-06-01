<?php

namespace Noxlogic\RateLimitBundle\Handlers;


class PdoHandler implements \SessionHandlerInterface
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
     * @var string Column for info
     */
    private $infoCol;

    /**
     * @var integer Column for lifetime
     */
    private $periodCol;

    /**
     * @var integer Column for time
     */
    private $resetCol;

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


    /**
     * @var bool Whether a transaction is active
     */
    private $inTransaction = false;

    /**
     * @var bool Whether gc() has been called
     */
    private $gcCalled = false;

    /**
     * It's an array to support multiple reads before closing which is manual, non-standard usage.
     *
     * @var \PDOStatement[] An array of statements to release advisory locks
     */
    private $unlockStatements = array();

    /**
     * This class is using the Symfony PDOSessionHandler as a template
     *
     * PdoHandler constructor.
     * @param null $pdoOrDsn
     * @param array $options
     */
    public function __construct($pdoOrDsn = null, array $options = array())
    {
        $pdoOrDsn = new \PDO($pdoOrDsn, $options['db_username'], $options['db_password']);
        $pdoOrDsn->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

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
        $this->infoCol = isset($options['db_info_col']) ? $options['db_info_col'] : $this->infoCol;
        $this->periodCol = isset($options['db_period_col']) ? $options['db_period_col'] : $this->periodCol;
        $this->resetCol = isset($options['db_reset_col']) ? $options['db_reset_col'] : $this->resetCol;
        $this->username = isset($options['db_username']) ? $options['db_username'] : $this->username;
        $this->password = isset($options['db_password']) ? $options['db_password'] : $this->password;
        $this->connectionOptions = isset($options['db_connection_options']) ? $options['db_connection_options'] : $this->connectionOptions;
        $this->lockMode = isset($options['lock_mode']) ? $options['lock_mode'] : $this->lockMode;

        $this->createTable();
        $this->writeToDB();
        $this->close();
    }

    /**
     * Return a PDO instance.
     *
     * @return \PDO
     */
    protected function getConnection()
    {
        if (null === $this->pdo) {
            $this->connect($this->dsn ?: ini_get('session.save_path'));
        }

        return $this->pdo;
    }

    public function createTable()
    {
        $this->getConnection();

        switch ($this->driver) {
            case 'pgsql':
            case 'mysql':
                $sql = 'CREATE TABLE IF NOT EXISTS noxlogic_database_cache (id VARCHAR(256) NOT NULL PRIMARY KEY, period INTEGER NOT NULL, info VARCHAR(255) NOT NULL, reset INTEGER NOT NULL)';
                break;
            default:
                throw new \DomainException(sprintf('Creating the database cache table is currently not implemented for PDO driver "%s".', $this->driver));
        }

        try {
            $this->pdo->exec($sql);
        } catch (\PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }

    }

    public function fetch($key){
        $this->inTransaction = true;

        return $this->read($key);
    }

    public function save($key, $info){
        $this->write($key, $info);
    }

    public function delete($key){
        $this->destroy($key);
    }

    public function writeToDB()
    {
        try {
            $this->commit();
        } catch (PDOException $e) {
            $this->rollBack();

            throw $e;
        }
    }

    /**
     * Helper method to begin a transaction.
     *
     * Since SQLite does not support row level locks, we have to acquire a reserved lock
     * on the database immediately. Because of https://bugs.php.net/42766 we have to create
     * such a transaction manually which also means we cannot use PDO::commit or
     * PDO::rollback or PDO::inTransaction for SQLite.
     *
     * Also MySQLs default isolation, REPEATABLE READ, causes deadlock for different sessions
     * due to http://www.mysqlperformanceblog.com/2013/12/12/one-more-innodb-gap-lock-to-avoid/ .
     * So we change it to READ COMMITTED.
     */
    private function beginTransaction()
    {
        if (!$this->inTransaction) {
            if ('sqlite' === $this->driver) {
                $this->pdo->exec('BEGIN IMMEDIATE TRANSACTION');
            } else {
                if ('mysql' === $this->driver) {
                    $this->pdo->exec('SET TRANSACTION ISOLATION LEVEL READ COMMITTED');
                }
                $this->pdo->beginTransaction();
            }
            $this->inTransaction = true;
        }
    }

    /**
     * Helper method to commit a transaction.
     */
    private function commit()
    {

        if ($this->inTransaction) {
            try {
                // commit read-write transaction which also releases the lock
                if ('sqlite' === $this->driver) {
                    $this->pdo->exec('COMMIT');
                } else {
                    $this->pdo->beginTransaction();
                    $this->pdo->commit();
                }
                $this->inTransaction = false;
            } catch (\PDOException $e) {
                $this->rollback();

                throw $e;
            }
        }
    }

    /**
     * Helper method to rollback a transaction.
     */
    private function rollback()
    {
        // We only need to rollback if we are in a transaction. Otherwise the resulting
        // error would hide the real problem why rollback was called. We might not be
        // in a transaction when not using the transactional locking behavior or when
        // two callbacks (e.g. destroy and write) are invoked that both fail.
        if ($this->inTransaction) {
            if ('sqlite' === $this->driver) {
                $this->pdo->exec('ROLLBACK');
            } else {
                $this->pdo->rollBack();
            }
            $this->inTransaction = false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function close()
    {
        $this->commit();

        while ($unlockStmt = array_shift($this->unlockStatements)) {
            $unlockStmt->execute();
        }

        if ($this->gcCalled) {
            $this->gcCalled = false;

            // delete the session records that have expired
            $sql = "DELETE FROM $this->table WHERE $this->periodCol + $this->resetCol < :time";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':time', time(), \PDO::PARAM_INT);
            $stmt->execute();
        }

        if (false !== $this->dsn) {
            $this->pdo = null; // only close lazy-connection
        }

        return true;
    }


    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        try {
            return $this->doRead($sessionId);
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }
    }

    /**
     * Reads the session data in respect to the different locking strategies.
     *
     * We need to make sure we do not return session data that is already considered garbage according
     * to the session.gc_maxlifetime setting because gc() is called after read() and only sometimes.
     *
     * @param string $sessionId Session ID
     *
     * @throws PDOException
     *
     * @return string The session data
     */
    private function doRead($sessionId)
    {
        $this->sessionExpired = false;

        if (self::LOCK_ADVISORY === $this->lockMode) {
            $this->unlockStatements[] = $this->doAdvisoryLock($sessionId);
        }

        $selectSql = $this->getSelectSql();
        $selectStmt = $this->pdo->prepare($selectSql);
        $selectStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);

        do {
            $selectStmt->execute();

            $sessionRows = $selectStmt->fetchAll(\PDO::FETCH_NUM);

            if ($sessionRows) {
                if ($sessionRows[0][1] + $sessionRows[0][2] < time()) {
                    $this->sessionExpired = true;

                    return '';
                }

                return is_resource($sessionRows[0][0]) ? stream_get_contents($sessionRows[0][0]) : $sessionRows[0][0];
            }

            if (self::LOCK_TRANSACTIONAL === $this->lockMode && 'sqlite' !== $this->driver) {
                // Exclusive-reading of non-existent rows does not block, so we need to do an insert to block
                // until other connections to the session are committed.
                try {
                    $insertStmt = $this->pdo->prepare(
                        "INSERT INTO $this->table ($this->idCol, $this->infoCol, $this->periodCol, $this->resetCol) VALUES (:id, :info, :period, :reset)"
                    );

                    $insertStmt->bindParam(':id', $sessionId, 2);
                    $insertStmt->bindValue(':info', '', 3);
                    $insertStmt->bindValue(':period', 0, 1);
                    $insertStmt->bindValue(':reset', time(), 1);

                    $insertStmt->execute();

                } catch (\PDOException $e) {
                    // Catch duplicate key error because other connection created the session already.
                    // It would only not be the case when the other connection destroyed the session.
                    if (0 === strpos($e->getCode(), '23')) {
                        // Retrieve finished session data written by concurrent connection by restarting the loop.
                        // We have to start a new transaction as a failed query will mark the current transaction as
                        // aborted in PostgreSQL and disallow further queries within it.
                        $this->rollback();
                        $this->beginTransaction();
                        continue;
                    }

                    throw $e;
                }
            }

            return '';
        } while (true);
    }

    /**
     * {@inheritdoc}
     */
    public function gc($maxlifetime)
    {
        // We delay gc() to close() so that it is executed outside the transactional and blocking read-write process.
        // This way, pruning expired sessions does not block them from being started while the current session is used.
        $this->gcCalled = true;

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId)
    {
        // delete the record associated with this id
        $sql = "DELETE FROM $this->table WHERE $this->idCol = :id";

        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
            $stmt->execute();
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data)
    {
        $maxlifetime = (int) ini_get('session.gc_maxlifetime');

        try {
            // We use a single MERGE SQL query when supported by the database.
            $mergeStmt = $this->getMergeStatement($sessionId, $data, $maxlifetime);

            if (null !== $mergeStmt) {

                $mergeStmt->execute();

                return true;
            }

            $updateStmt = $this->pdo->prepare(
                "UPDATE $this->table SET $this->infoCol = :info, $this->periodCol = :period, $this->resetCol = :reset WHERE $this->idCol = :id"
            );
            $updateStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
            $updateStmt->bindParam(':info', $data, \PDO::PARAM_LOB);
            $updateStmt->bindParam(':period', $maxlifetime, \PDO::PARAM_INT);
            $updateStmt->bindValue(':reset', time(), \PDO::PARAM_INT);

            $updateStmt->execute();

            // When MERGE is not supported, like in Postgres < 9.5, we have to use this approach that can result in
            // duplicate key errors when the same session is written simultaneously (given the LOCK_NONE behavior).
            // We can just catch such an error and re-execute the update. This is similar to a serializable
            // transaction with retry logic on serialization failures but without the overhead and without possible
            // false positives due to longer gap locking.
            if (!$updateStmt->rowCount()) {
                try {
                    $insertStmt = $this->pdo->prepare(
                        "INSERT INTO $this->table ($this->idCol, $this->infoCol, $this->periodCol, $this->resetCol) VALUES (:id, :info, :period, :reset)"
                    );
                    $insertStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
                    $insertStmt->bindParam(':info', $data, \PDO::PARAM_LOB);
                    $insertStmt->bindParam(':period', $maxlifetime, \PDO::PARAM_INT);
                    $insertStmt->bindValue(':reset', time(), \PDO::PARAM_INT);
                    $insertStmt->execute();
                } catch (\PDOException $e) {
                    // Handle integrity violation SQLSTATE 23000 (or a subclass like 23505 in Postgres) for duplicate keys
                    if (0 === strpos($e->getCode(), '23')) {
                        $updateStmt->execute();
                    } else {
                        throw $e;
                    }
                }
            }
        } catch (\PDOException $e) {
            $this->rollback();

            throw $e;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName)
    {
        if (null === $this->pdo) {
            $this->connect($this->dsn ?: $savePath);
        }

        return true;
    }

    /**
     * Return a locking or nonlocking SQL query to read session information.
     *
     * @return string The SQL string
     *
     * @throws \DomainException When an unsupported PDO driver is used
     */
    private function getSelectSql()
    {
        if (self::LOCK_TRANSACTIONAL === $this->lockMode) {
            $this->beginTransaction();

            switch ($this->driver) {
                case 'mysql':
                case 'oci':
                case 'pgsql':
                    return "SELECT $this->infoCol, $this->periodCol, $this->resetCol FROM $this->table WHERE $this->idCol = :id FOR UPDATE";

                default:
                    throw new \DomainException(sprintf('Transactional locks are currently not implemented for PDO driver "%s".', $this->driver));
            }
        }

        return "SELECT $this->infoCol, $this->periodCol, $this->resetCol FROM $this->table WHERE $this->idCol = :id";
    }

    /**
     * Returns a merge/upsert (i.e. insert or update) statement when supported by the database for writing session data.
     *
     * @param string $sessionId   Session ID
     * @param string $data        Encoded session data
     * @param int    $maxlifetime session.gc_maxlifetime
     *
     * @return \PDOStatement|null The merge statement or null when not supported
     */
    private function getMergeStatement($sessionId, $data, $maxlifetime)
    {
        $mergeSql = null;
        switch (true) {

            case 'pgsql' === $this->driver && version_compare($this->pdo->getAttribute(\PDO::ATTR_SERVER_VERSION), '9.5', '>='):
                $mergeSql = "INSERT INTO $this->table ($this->idCol, $this->infoCol, $this->periodCol, $this->resetCol) VALUES (:id, :info, :period, :reset) ".
                    "ON CONFLICT ($this->idCol) DO UPDATE SET ($this->infoCol, $this->periodCol, $this->resetCol) = (EXCLUDED.$this->infoCol, EXCLUDED.$this->periodCol, EXCLUDED.$this->resetCol)";
                break;
        }

        if (null !== $mergeSql) {
            $mergeStmt = $this->pdo->prepare($mergeSql);

            if ('pgsql' === $this->driver) {

                $mergeStmt->bindParam(':id', $sessionId, \PDO::PARAM_STR);
                $mergeStmt->bindParam(':info', $data, \PDO::PARAM_LOB);
                $mergeStmt->bindParam(':period', $maxlifetime, \PDO::PARAM_INT);
                $mergeStmt->bindValue(':reset', time(), \PDO::PARAM_INT);
            }

            return $mergeStmt;
        }
    }
}