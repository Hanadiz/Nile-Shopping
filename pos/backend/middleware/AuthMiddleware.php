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
            $this->forbidden('You do not have permission to perform this action');
        }
        
        return $user;
    }
    
    /**
     * Check if user has permission
     */
    private function hasPermission($role, $permission) {
        global $PERMISSIONS;
        
        if ($role === ROLE_ADMIN) {
            return true;
        }
        
        $rolePermissions = $PERMISSIONS[$role] ?? [];
        return in_array($permission, $rolePermissions);
    }
    
    /**
     * Verify JWT token
     */
    private function verifyToken($token) {
        // For demo, accept any valid-looking token
        if (strlen($token) > 20) {
            // Decode token (simplified for demo)
            $payload = $this->decodeJWT($token);
            if ($payload) {
                $this->currentUser = [
                    'id' => $payload['user_id'] ?? 1,
                    'email' => $payload['email'] ?? 'admin@nile.com',
                    'role' => $payload['role'] ?? ROLE_ADMIN,
                    'tier' => $payload['tier'] ?? TIER_FREEMIUM,
                    'store_id' => $payload['store_id'] ?? 1
                ];
                return true;
            }
        }
        
        // Demo mode: accept demo token
        if ($token === 'demo_token_123') {
            $this->currentUser = [
                'id' => 1,
                'email' => 'demo@nile.com',
                'role' => ROLE_ADMIN,
                'tier' => TIER_FREEMIUM,
                'store_id' => 1
            ];
            return true;
        }
        
        return false;
    }
    
    /**
     * Decode JWT token (simplified)
     */
    private function decodeJWT($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }
        
        $payload = base64_decode($parts[1]);
        return json_decode($payload, true);
    }
    
    /**
     * Generate JWT token
     */
    public function generateToken($user) {
        $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
        $payload = json_encode([
            'user_id' => $user['id'],
            'email' => $user['email'],
            'role' => $user['role'],
            'tier' => $user['tier'],
            'store_id' => $user['store_id'],
            'exp' => time() + JWT_EXPIRY
        ]);
        
        $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
        $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));
        $signature = hash_hmac('sha256', $base64UrlHeader . '.' . $base64UrlPayload, JWT_SECRET, true);
        $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
        
        return $base64UrlHeader . '.' . $base64UrlPayload . '.' . $base64UrlSignature;
    }
    
    /**
     * Get authorization header
     */
    private function getAuthorizationHeader() {
        $headers = null;
        
        if (isset($_SERVER['Authorization'])) {
            $headers = trim($_SERVER['Authorization']);
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $headers = trim($_SERVER['HTTP_AUTHORIZATION']);
        } elseif (function_exists('apache_request_headers')) {
            $requestHeaders = apache_request_headers();
            $requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
            if (isset($requestHeaders['Authorization'])) {
                $headers = trim($requestHeaders['Authorization']);
            }
        }
        
        return $headers;
    }
    
    /**
     * Send unauthorized response
     */
    private function unauthorized($message = 'Unauthorized') {
        http_response_code(HTTP_UNAUTHORIZED);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => HTTP_UNAUTHORIZED
        ]);
        exit;
    }
    
    /**
     * Send forbidden response
     */
    private function forbidden($message = 'Forbidden') {
        http_response_code(HTTP_FORBIDDEN);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => HTTP_FORBIDDEN
        ]);
        exit;
    }
    
    /**
     * Get current authenticated user
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }
}

/**
 * Helper function to require authentication
 */
function requireAuth() {
    return AuthMiddleware::getInstance()->validate();
}

/**
 * Helper function to require specific role
 */
function requireRole($role) {
    return AuthMiddleware::getInstance()->requireRole($role);
}

/**
 * Helper function to require permission
 */
function requirePermission($permission) {
    return AuthMiddleware::getInstance()->requirePermission($permission);
}
