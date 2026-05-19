
<?php
/**
 * CORS Middleware
 * Nile Shopping POS - Cross-Origin Resource Sharing
 */

class CorsMiddleware {
    
    /**
     * Handle CORS headers
     */
    public static function handle() {
        // Allow from any origin in development
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        if (in_array($origin, CORS_ALLOWED_ORIGINS) || APP_ENV === 'development') {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        header("Access-Control-Allow-Methods: " . CORS_ALLOWED_METHODS);
        header("Access-Control-Allow-Headers: " . CORS_ALLOWED_HEADERS);
        header("Access-Control-Allow-Credentials: true");
        header("Access-Control-Max-Age: 86400");
        
        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit;
        }
    }
}

// Handle CORS on every request
CorsMiddleware::handle();
