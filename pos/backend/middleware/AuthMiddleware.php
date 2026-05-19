
<?php
/**
 * Authentication Middleware
 * Nile Shopping POS - JWT Authentication & Session Management
 */

require_once dirname(__DIR__) . '/config/pos_config.php';
require_once dirname(__DIR__) . '/config/constants.php';

class AuthMiddleware {
    private static $instance = null;
    private $currentUser = null;
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Validate JWT token from request headers
     */
    public function validate() {
        $headers = $this->getAuthorizationHeader();
        
        if (!$headers) {
            $this->unauthorized('Authorization header missing');
        }
        
        $token = str_replace('Bearer ', '', $headers);
        
        if (!$this->verifyToken($token)) {
            $this->unauthorized('Invalid or expired token');
        }
        
        return $this->currentUser;
    }
    
    /**
     * Validate with specific role requirement
     */
    public function requireRole($role) {
        $user = $this->validate();
        
        if ($user['role'] !== $role && $user['role'] !== ROLE_ADMIN) {
            $this->forbidden('Insufficient permissions. Required role: ' . $role);
        }
        
        return $user;
    }
    
    /**
     * Validate with specific permission
     */
    public function requirePermission($permission) {
        $user = $this->validate();
        
        if (!$this->hasPermission($user['role'], $permission)) {
            $this->forbidden('You do not have permission to
