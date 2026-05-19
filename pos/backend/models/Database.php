
<?php
/**
 * Database Model
 * Nile Shopping POS - Extended Database Operations
 */

require_once dirname(__DIR__) . '/config/database.php';

class DatabaseModel {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * Find record by ID
     */
    public function find($id) {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        return $this->db->fetchOne($sql, [':id' => $id]);
    }
    
    /**
     * Find all records with optional filters
     */
    public function findAll($filters = [], $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        if ($limit) {
            $sql .= " LIMIT {$limit} OFFSET {$offset}";
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * Create new record
     */
    public function create($data) {
        return $this->db->insert($this->table, $data);
    }
    
    /**
     * Update record
     */
    public function update($id, $data) {
        return $this->db->update($this->table, $data, "{$this->primaryKey} = :id", [':id' => $id]);
    }
    
    /**
     * Delete record
     */
    public function delete($id) {
        return $this->db->delete($this->table, "{$this->primaryKey} = :id", [':id' => $id]);
    }
    
    /**
     * Count records
     */
    public function count($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];
        
        if (!empty($filters)) {
            $conditions = [];
            foreach ($filters as $key => $value) {
                $conditions[] = "{$key} = :{$key}";
                $params[":{$key}"] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
    
    /**
     * Begin transaction
     */
    public function beginTransaction() {
        $this->db->beginTransaction();
    }
    
    /**
     * Commit transaction
     */
    public function commit() {
        $this->db->commit();
    }
    
    /**
     * Rollback transaction
     */
    public function rollback() {
        $this->db->rollback();
    }
}
