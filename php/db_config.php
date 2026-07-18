<?php
/**
 * Database configuration
 * PostgreSQL wrapper for legacy mysqli-style usage.
 */

$databaseUrl = getenv('DATABASE_URL');
$dbOptions = '';
if ($databaseUrl) {
    $parsed = parse_url($databaseUrl);
    if ($parsed === false || !in_array($parsed['scheme'] ?? '', ['pgsql', 'postgres', 'postgresql'])) {
        die(json_encode(['status' => 'error', 'message' => 'Unsupported DATABASE_URL scheme. Expected postgres:// or pgsql://']));
    }

    $host = $parsed['host'] ?? 'localhost';
    $user = $parsed['user'] ?? 'postgres';
    $pass = $parsed['pass'] ?? '';
    $db   = isset($parsed['path']) ? ltrim($parsed['path'], '/') : 'cmsp_db';
    $port = $parsed['port'] ?? 5432;

    if (!empty($parsed['query'])) {
        parse_str($parsed['query'], $queryParams);
        foreach ($queryParams as $key => $value) {
            $dbOptions .= sprintf(' %s=%s', $key, $value);
        }
    }
} else {
    $host = getenv('DB_HOST') ?: 'localhost';
    $user = getenv('DB_USER') ?: 'postgres';
    $pass = getenv('DB_PASS') ?: '';
    $db   = getenv('DB_NAME') ?: 'cmsp_db';
    $port = getenv('DB_PORT') ?: 5432;
}

class DBResult {
    private $result;

    public function __construct($result) {
        $this->result = $result;
    }

    public function fetch_assoc() {
        if (!$this->result) {
            return false;
        }
        return pg_fetch_assoc($this->result);
    }

    public function num_rows() {
        if (!$this->result) {
            return 0;
        }
        return pg_num_rows($this->result);
    }
}

class DBStatement {
    private $conn;
    private $name;
    private $params = [];
    public $error;
    public $affected_rows = 0;
    public $insert_id = null;
    private $result;

    public function __construct($conn, $name) {
        $this->conn = $conn;
        $this->name = $name;
    }

    public function bind_param() {
        $args = func_get_args();
        array_shift($args);
        $this->params = $args;
        return true;
    }

    public function execute() {
        $this->result = pg_execute($this->conn, $this->name, $this->params);
        if ($this->result === false) {
            $this->error = pg_last_error($this->conn);
            return false;
        }

        $this->affected_rows = pg_affected_rows($this->result);
        $row = pg_fetch_assoc($this->result);
        if ($row && isset($row['id'])) {
            $this->insert_id = $row['id'];
            pg_result_seek($this->result, 0);
        }

        return true;
    }

    public function get_result() {
        return new DBResult($this->result);
    }
}

class DB {
    public $error;
    private $conn;
    private static $stmtCounter = 0;

    public function __construct($host, $user, $pass, $db, $port, $options = '') {
        $connectionString = sprintf('host=%s port=%d dbname=%s user=%s password=%s',
            $host,
            $port,
            $db,
            $user,
            $pass
        );

        if (!empty($options)) {
            $connectionString .= $options;
        }

        $this->conn = pg_connect($connectionString);
        if ($this->conn === false) {
            die(json_encode(['status' => 'error', 'message' => 'DB Connection failed: ' . pg_last_error()]));
        }
    }

    public function query($sql) {
        $result = pg_query($this->conn, $sql);
        if ($result === false) {
            $this->error = pg_last_error($this->conn);
            return false;
        }
        return new DBResult($result);
    }

    public function prepare($sql) {
        self::$stmtCounter++;
        $name = 'stmt_' . self::$stmtCounter;
        $preparedSql = $this->convertQuestionMarks($sql);
        $result = pg_prepare($this->conn, $name, $preparedSql);
        if ($result === false) {
            $this->error = pg_last_error($this->conn);
            return false;
        }

        return new DBStatement($this->conn, $name);
    }

    public function real_escape_string($string) {
        return pg_escape_string($this->conn, $string);
    }

    public function begin_transaction() {
        return pg_query($this->conn, 'BEGIN');
    }

    public function commit() {
        return pg_query($this->conn, 'COMMIT');
    }

    public function rollback() {
        return pg_query($this->conn, 'ROLLBACK');
    }

    public function set_charset($charset) {
        return true;
    }

    private function convertQuestionMarks($sql) {
        $count = 0;
        return preg_replace_callback('/\?/', function () use (&$count) {
            $count++;
            return '$' . $count;
        }, $sql);
    }
}

$conn = new DB($host, $user, $pass, $db, $port, $dbOptions);
?>
