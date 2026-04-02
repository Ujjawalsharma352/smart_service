<?php
// API Configuration
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Include main database config
require_once '../config/db.php';

// API Response Helper
class APIResponse {
    public static function success($data = null, $message = 'Success') {
        echo json_encode([
            'success' => true,
            'message' => $message,
            'data' => $data,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function error($message = 'Error', $code = 400) {
        http_response_code($code);
        echo json_encode([
            'success' => false,
            'message' => $message,
            'timestamp' => date('Y-m-d H:i:s')
        ]);
    }
    
    public static function unauthorized() {
        self::error('Unauthorized', 401);
    }
    
    public static function forbidden() {
        self::error('Forbidden', 403);
    }
    
    public static function notFound() {
        self::error('Not Found', 404);
    }
}

// API Authentication Helper
class APIAuth {
    public static function requireAuth() {
        if (!isLoggedIn()) {
            APIResponse::unauthorized();
            exit;
        }
    }
    
    public static function requireRole($role) {
        self::requireAuth();
        if (getUserRole() !== $role) {
            APIResponse::forbidden();
            exit;
        }
    }
    
    public static function getCurrentUser() {
        if (!isLoggedIn()) {
            return null;
        }
        
        $userId = getUserId();
        global $conn;
        
        return $conn->query("SELECT id, name, email, role, phone, address FROM users WHERE id = $userId")->fetch_assoc();
    }
}

// API Input Helper
class APIInput {
    public static function getJSON() {
        $input = file_get_contents('php://input');
        return json_decode($input, true) ?: [];
    }
    
    public static function get($key, $default = null) {
        return $_GET[$key] ?? $default;
    }
    
    public static function post($key, $default = null) {
        return $_POST[$key] ?? $default;
    }
    
    public static function validate($data, $required) {
        $missing = [];
        
        foreach ($required as $field) {
            if (empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            APIResponse::error('Missing required fields: ' . implode(', ', $missing));
            return false;
        }
        
        return true;
    }
}
?>
