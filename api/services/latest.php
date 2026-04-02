<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $lastCheck = $_GET['last_check'] ?? 0;
    
    // Get services created after the last check
    $sql = "SELECT COUNT(*) as new_services FROM services 
            WHERE status = 'active' AND created_at > FROM_UNIXTIME($lastCheck)";
    
    $result = $conn->query($sql);
    $data = $result->fetch_assoc();
    
    APIResponse::success([
        'new_services' => (int)$data['new_services'],
        'last_check' => time()
    ], 'Latest services count retrieved');
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
