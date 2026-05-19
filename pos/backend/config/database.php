<?php
/**
 * Database Configuration
 * Nile Shopping POS - Database Connection Settings
 * 
 * @package NilePOS
 * @version 2.0
 */

// ============================================
// DATABASE CREDENTIALS
// ============================================

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'nile_pos');
define('DB_USER', getenv('DB_USER') ?: 'pos_user');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_PORT', getenv('DB_PORT') ?: '3306');
define('DB_CHARSET', 'utf8mb4');

// ============================================
// CONNECTION POOLING SETTINGS
// ============================================

define('DB_MAX_CONNECTIONS', 10);
define('DB_TIMEOUT', 5);
define('DB_PERSISTENT', true);

// ============================================
// DEMO MODE (when no database available)
// ============================================

define('DEMO_MODE', true); // Set to false in production

// ============================================
// DATABASE CLASS
// ============================================

class Database {
    private static $instance = null;
    private $connection;
    private $statement;
    private $inTransaction = false;
    private $demoData = [];
    
    /**
     * Private constructor (singleton pattern)
     */
    private function __construct() {
        $this->initDemoData();
        
        if (!DEMO_MODE) {
            try {
                $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => DB_PERSISTENT,
                    PDO::ATTR_TIMEOUT => DB_TIMEOUT
                ]);
                error_log("Database connected successfully");
            } catch (PDOException $e) {
                error_log("Database connection failed: " . $e->getMessage());
                $this->connection = null;
            }
        }
    }
    
    /**
     * Initialize demo data for offline/development mode
     */
    private function initDemoData() {
        $this->demoData = [
            'users' => [
                ['id' => 1, 'email' => 'admin@nile.com', 'password' => password_hash('Admin123!', PASSWORD_DEFAULT), 'name' => 'Admin User', 'role' => 'admin', 'store_id' => 1, 'is_active' => 1],
                ['id' => 2, 'email' => 'manager@nile.com', 'password' => password_hash('Manager123!', PASSWORD_DEFAULT), 'name' => 'Manager User', 'role' => 'manager', 'store_id' => 1, 'is_active' => 1],
                ['id' => 3, 'email' => 'cashier@nile.com', 'password' => password_hash('Cashier123!', PASSWORD_DEFAULT), 'name' => 'Cashier User', 'role' => 'cashier', 'store_id' => 1, 'is_active' => 1]
            ],
            'products' => [
                ['id' => 1, 'sku' => 'PRD-001', 'name' => 'Classic T-Shirt', 'price' => 29.99, 'cost' => 15.00, 'stock' => 48, 'category' => 'Apparel', 'barcode' => '8901234567890', 'is_active' => 1],
                ['id' => 2, 'sku' => 'PRD-002', 'name' => 'Slim Fit Jeans', 'price' => 59.99, 'cost' => 30.00, 'stock' => 35, 'category' => 'Apparel', 'barcode' => '8901234567891', 'is_active' => 1],
                ['id' => 3, 'sku' => 'PRD-003', 'name' => 'Running Shoes', 'price' => 89.99, 'cost' => 45.00, 'stock' => 12, 'category' => 'Footwear', 'barcode' => '8901234567892', 'is_active' => 1],
                ['id' => 4, 'sku' => 'PRD-004', 'name' => 'Leather Wallet', 'price' => 39.99, 'cost' => 20.00, 'stock' => 45, 'category' => 'Accessories', 'barcode' => '8901234567893', 'is_active' => 1],
                ['id' => 5, 'sku' => 'PRD-005', 'name' => 'Smart Watch', 'price' => 199.99, 'cost' => 120.00, 'stock' => 8, 'category' => 'Electronics', 'barcode' => '8901234567894', 'is_active' => 1],
                ['id' => 6, 'sku' => 'PRD-006', 'name' => 'Wireless Earbuds', 'price' => 79.99, 'cost' => 40.00, 'stock' => 3, 'category' => 'Electronics', 'barcode' => '8901234567895', 'is_active' => 1],
                ['id' => 7, 'sku' => 'PRD-007', 'name' => 'Sunglasses', 'price' => 49.99, 'cost' => 25.00, 'stock' => 28, 'category' => 'Accessories', 'barcode' => '8901234567896', 'is_active' => 1],
                ['id' => 8, 'sku' => 'PRD-008', 'name' => 'Backpack', 'price' => 69.99, 'cost' => 35.00, 'stock' => 18, 'category' => 'Accessories', 'barcode' => '8901234567897', 'is_active' => 1]
            ],
            'customers' => [
                ['id' => 1, 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john@example.com', 'phone' => '555-0101', 'points' => 1250, 'tier' => 'gold', 'total_spent' => 1240.50],
                ['id' => 2, 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane@example.com', 'phone' => '555-0102', 'points' => 3450, 'tier' => 'platinum', 'total_spent' => 2780.00],
                ['id' => 3, 'first_name' => 'Bob', 'last_name' => 'Johnson', 'email' => 'bob@example.com', 'phone' => '555-0103', 'points' => 450, 'tier' => 'silver', 'total_spent' => 890.75]
            ],
            'transactions' => [
                ['id' => 1, 'transaction_number' => 'TRX-20240115-001', 'user_id' => 3, 'customer_id' => 1, 'total' => 142.97, 'tax' => 13.00, 'status' => 'completed', 'created_at' => '2024-01-15 14:30:00'],
                ['id' => 2, 'transaction_number' => 'TRX-20240115-002', 'user_id' => 3, 'customer_id' => 2, 'total' => 219.99, 'tax' => 20.00, 'status' => 'completed', 'created_at' => '2024-01-15 10:15:00']
            ],
            'stores' => [
                ['id' => 1, 'name' => 'Nile Downtown', 'address' => '123 Main Street', 'phone' => '(555) 123-4567', 'tax_rate' => 10.00]
            ],
            'registers' => [
                ['id' => 1, 'store_id' => 1, 'name' => 'Main Counter', 'status' => 'online']
            ]
        ];
    }
    
    /**
     * Get singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Get PDO connection
     */
    public function getConnection() {
        return $this->connection;
    }
    
    /**
     * Check if connected to real database
     */
    public function isConnected() {
        return $this->connection !== null;
    }
    
    /**
     * Execute a query
     */
    public function query($sql, $params = []) {
        if ($this->isConnected() && !DEMO_MODE) {
            $this->statement = $this->connection->prepare($sql);
            $this->statement->execute($params);
            return $this->statement;
        }
        
        // Demo mode - simulate database
        return $this->demoQuery($sql, $params);
    }
    
    /**
     * Fetch all rows
     */
    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        
        if ($this->isConnected() && !DEMO_MODE) {
            return $result->fetchAll();
        }
        
        return $result;
    }
    
    /**
     * Fetch single row
     */
    public function fetchOne($sql, $params = []) {
        $results = $this->fetchAll($sql, $params);
        return $results[0] ?? null;
    }
    
    /**
     * Insert a record
     */
    public function insert($table, $data) {
        if ($this->isConnected() && !DEMO_MODE) {
            $fields = array_keys($data);
            $placeholders = ':' . implode(', :', $fields);
            $sql = "INSERT INTO {$table} (" . implode(', ', $fields) . ") VALUES (" . $placeholders . ")";
            $this->query($sql, $data);
            return $this->connection->lastInsertId();
        }
        
        // Demo mode
        return $this->demoInsert($table, $data);
    }
    
    /**
     * Update records
     */
    public function update($table, $data, $where, $whereParams = []) {
        if ($this->isConnected() && !DEMO_MODE) {
            $set = [];
            foreach ($data as $key => $value) {
                $set[] = "{$key} = :{$key}";
            }
            $sql = "UPDATE {$table} SET " . implode(', ', $set) . " WHERE {$where}";
            return $this->query($sql, array_merge($data, $whereParams))->rowCount();
        }
        
        // Demo mode
        return $this->demoUpdate($table, $data, $where, $whereParams);
    }
    
    /**
     * Delete records
     */
    public function delete($table, $where, $params = []) {
        if ($this->isConnected() && !DEMO_MODE) {
            $sql = "DELETE FROM {$table} WHERE {$where}";
            return $this->query($sql, $params)->rowCount();
        }
        
        return 1; // Demo always succeeds
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        if ($this->isConnected() && !DEMO_MODE) {
            $this->connection->beginTransaction();
        }
        $this->inTransaction = true;
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        if ($this->isConnected() && !DEMO_MODE && $this->inTransaction) {
            $this->connection->commit();
        }
        $this->inTransaction = false;
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        if ($this->isConnected() && !DEMO_MODE && $this->inTransaction) {
            $this->connection->rollback();
        }
        $this->inTransaction = false;
    }
    
    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        if ($this->isConnected() && !DEMO_MODE) {
            return $this->connection->lastInsertId();
        }
        return rand(100, 99999);
    }
    
    /**
     * Demo mode query handler
     */
    private function demoQuery($sql, $params = []) {
        $sqlLower = strtolower(trim($sql));
        
        // Parse SELECT queries
        if (strpos($sqlLower, 'select') === 0) {
            return $this->demoSelect($sql, $params);
        }
        
        return [];
    }
    
    /**
     * Demo SELECT handler
     */
    private function demoSelect($sql, $params = []) {
        if (strpos($sql, 'users') !== false) {
            $results = $this->demoData['users'];
            
            // Filter by email
            if (isset($params[':email'])) {
                $results = array_filter($results, fn($u) => $u['email'] === $params[':email']);
            }
            
            return array_values($results);
        }
        
        if (strpos($sql, 'products') !== false) {
            $results = $this->demoData['products'];
            
            // Filter by SKU/barcode
            if (isset($params[':sku'])) {
                $results = array_filter($results, fn($p) => $p['sku'] === $params[':sku']);
            }
            if (isset($params[':barcode'])) {
                $results = array_filter($results, fn($p) => $p['barcode'] === $params[':barcode']);
            }
            
            return array_values($results);
        }
        
        if (strpos($sql, 'customers') !== false) {
            $results = $this->demoData['customers'];
            
            if (isset($params[':email'])) {
                $results = array_filter($results, fn($c) => $c['email'] === $params[':email']);
            }
            if (isset($params[':phone'])) {
                $results = array_filter($results, fn($c) => $c['phone'] === $params[':phone']);
            }
            
            return array_values($results);
        }
        
        if (strpos($sql, 'transactions') !== false) {
            return $this->demoData['transactions'];
        }
        
        return [];
    }
    
    /**
     * Demo INSERT handler
     */
    private function demoInsert($table, $data) {
        if (!isset($this->demoData[$table])) {
            $this->demoData[$table] = [];
        }
        
        $newId = count($this->demoData[$table]) + 1;
        $data['id'] = $newId;
        $this->demoData[$table][] = $data;
        
        return $newId;
    }
    
    /**
     * Demo UPDATE handler
     */
    private function demoUpdate($table, $data, $where, $whereParams = []) {
        if (!isset($this->demoData[$table])) {
            return 0;
        }
        
        $count = 0;
        foreach ($this->demoData[$table] as $index => $row) {
            $match = true;
            // Simple where parsing for demo
            if (strpos($where, 'id = :id') !== false && isset($whereParams[':id'])) {
                if ($row['id'] != $whereParams[':id']) {
                    $match = false;
                }
            }
            
            if ($match) {
                foreach ($data as $key => $value) {
                    $this->demoData[$table][$index][$key] = $value;
                }
                $count++;
            }
        }
        
        return $count;
    }
    
    /**
     * Get demo data for testing
     */
    public function getDemoData($table = null) {
        if ($table) {
            return $this->demoData[$table] ?? [];
        }
        return $this->demoData;
    }
    
    /**
     * Prepare statement (for compatibility)
     */
    public function prepare($sql) {
        if ($this->isConnected() && !DEMO_MODE) {
            return $this->connection->prepare($sql);
        }
        return new DemoStatement($sql);
    }
}

/**
 * Demo Statement Class for compatibility
 */
class DemoStatement {
    private $sql;
    private $params = [];
    
    public function __construct($sql) {
        $this->sql = $sql;
    }
    
    public function execute($params = []) {
        $this->params = $params;
        return true;
    }
    
    public function fetch() {
        return null;
    }
    
    public function fetchAll() {
        return [];
    }
    
    public function rowCount() {
        return 0;
    }
}

/**
 * Helper function to get database instance
 */
function db() {
    return Database::getInstance();
}
