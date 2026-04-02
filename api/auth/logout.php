<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isLoggedIn()) {
        // Destroy session
        session_destroy();
        
        APIResponse::success(null, 'Logout successful');
    } else {
        APIResponse::error('No active session');
    }
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
