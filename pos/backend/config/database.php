<?php
/**
 * Database Configuration
 * Nile Shopping POS - Database Connection Settings
 */

// Database credentials
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'nile_pos');
define('DB_USER', getenv('DB_USER') ?: 'pos_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');

// Connection pooling settings
define('DB_MAX_CONNECTIONS', 10);
define('DB_TIMEOUT', 5);

/**
 * Database Connection Class
 * Handles PDO connections with singleton pattern
 */
class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => true
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            // For demo, create in-memory fallback
            $this->connection = null;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function query($sql, $params = []) {
        if (!$this->connection) {
            return $this->mockQuery($sql, $params);
        }
        $this->statement = $this->connection->prepare($sql);
        $this->statement->execute($params);
        return $this->statement;
    }
    
    public function fetchAll($sql, $params = []) {
        return $this->query($sql, $params)->fetchAll();
    }
    
    public function fetchOne($sql, $params = []) {
        return $this->query($sql, $params)->fetch();
    }
    
    public function insert($table, $data) {
        $fields = array_keys($data);
        $placeholders = ':' . implode(', :', $fields);
        $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
        $this->query($sql, $data);
        return $this->connection ? $this->connection->lastInsertId() : rand(1000, 9999);
    }
    
    public function update($table, $data, $where, $whereParams = []) {
        $set = [];
        foreach ($data as $key => $value) {
            $set[] = "{$key} = :{$key}";
        }
        $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
        return $this->query($sql, array_merge($data, $whereParams))->rowCount();
    }
    
    public function beginTransaction() {
        if ($this->connection) $this->connection->beginTransaction();
    }
    
    public
