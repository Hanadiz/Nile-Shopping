
<?php
/**
 * Rate Limiter Middleware
 * Nile Shopping POS - Prevent API Abuse
 */

class RateLimiter {
    private static $instance = null;
    private $storage = [];
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if request is within limits
     */
    public function check($key = null, $limit = null, $window = 60) {
        $key = $key ?: $this->getClientKey();
        $limit = $limit ?: RATE_LIMIT_REQUESTS;
        
        $this->cleanup();
        
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = ['count' => 1, 'reset' => time() + $window];
            return true;
        }
        
        $data = $this->storage[$key];
        
        if (time() > $data['reset']) {
            $this->storage[$key] = ['count' => 1, 'reset' => time() + $window];
            return true;
        }
        
        if ($data['count'] >= $limit) {
            $this->rateLimited($data['reset'] - time());
            return false;
        }
        
        $this->storage[$key]['count']++;
        return true;
    }
    
    /**
     * Check login rate limit (stricter)
     */
    public function checkLogin($email) {
        $key = 'login_' . $email . '_' . $_SERVER['REMOTE_ADDR'];
        $limit = RATE_LIMIT_LOGIN;
        $window = 900; // 15 minutes
        
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = ['count' => 1, 'reset' => time() + $window];
            return true;
        }
        
        $data = $this->storage[$key];
        
        if (time() > $data['reset']) {
            $this->storage[$key] = ['count' => 1, 'reset' => time() + $window];
            return true;
        }
        
        if ($data['count'] >= $limit) {
            $this->rateLimited($data['reset'] - time(), 'Too many login attempts');
            return false;
        }
        
        $this->storage[$key]['count']++;
        return true;
    }
    
    /**
     * Record failed login attempt
     */
    public function recordFailedLogin($email) {
        $key = 'login_' . $email . '_' . $_SERVER['REMOTE_ADDR'];
        
        if (!isset($this->storage[$key])) {
            $this->storage[$key] = ['count' => 1, 'reset' => time() + 900];
        } else {
            $this->storage[$key]['count']++;
        }
    }
    
    /**
     * Reset rate limit for a key
     */
    public function reset($key) {
        unset($this->storage[$key]);
    }
    
    /**
     * Get client identifier key
     */
    private function getClientKey() {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return md5($ip . $userAgent);
    }
    
    /**
     * Clean up expired entries
     */
    private function cleanup() {
        foreach ($this->storage as $key => $data) {
            if (time() > $data['reset']) {
                unset($this->storage[$key]);
            }
        }
    }
    
    /**
     * Send rate limit response
     */
    private function rateLimited($retryAfter, $message = 'Too many requests') {
        http_response_code(HTTP_TOO_MANY_REQUESTS);
        header('Content-Type: application/json');
        header("Retry-After: $retryAfter");
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => HTTP_TOO_MANY_REQUESTS,
            'retry_after' => $retryAfter
        ]);
        exit;
    }
}

/**
 * Helper function to check rate limit
 */
function checkRateLimit($key = null, $limit = null) {
    return RateLimiter::getInstance()->check($key, $limit);
}

/**
 * Helper function to check login rate limit
 */
function checkLoginRateLimit($email) {
    return RateLimiter::getInstance()->checkLogin($email);
}
