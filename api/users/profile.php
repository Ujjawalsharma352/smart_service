<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    APIAuth::requireAuth();
    
    $user = APIAuth::getCurrentUser();
    
    if ($user) {
        // Get user statistics
        $userId = $user['id'];
        
        if ($user['role'] === 'user') {
            $stats = [
                'total_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId")->fetch_assoc()['count'],
                'pending_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId AND status = 'pending'")->fetch_assoc()['count'],
                'completed_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId AND status = 'completed'")->fetch_assoc()['count'],
                'total_spent' => $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.user_id = $userId AND b.status = 'completed'")->fetch_assoc()['total'] ?? 0
            ];
        } else if ($user['role'] === 'provider') {
            $stats = [
                'total_services' => $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $userId")->fetch_assoc()['count'],
                'active_services' => $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $userId AND status = 'active'")->fetch_assoc()['count'],
                'total_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $userId")->fetch_assoc()['count'],
                'completed_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $userId AND status = 'completed'")->fetch_assoc()['count'],
                'total_earnings' => $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.provider_id = $userId AND b.status = 'completed'")->fetch_assoc()['total'] ?? 0,
                'average_rating' => $conn->query("SELECT AVG(rating) as avg FROM reviews WHERE provider_id = $userId")->fetch_assoc()['avg'] ?? 0
            ];
        }
        
        $user['statistics'] = $stats;
        APIResponse::success($user);
    } else {
        APIResponse::error('User not found');
    }
    
} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    APIAuth::requireAuth();
    
    $data = APIInput::getJSON();
    $userId = getUserId();
    
    // Update user profile
    $name = clean_input($data['name'] ?? '');
    $email = clean_input($data['email'] ?? '');
    $phone = clean_input($data['phone'] ?? '');
    $address = clean_input($data['address'] ?? '');
    
    // Validate required fields
    if (empty($name) || empty($email)) {
        APIResponse::error('Name and email are required');
        exit;
    }
    
    // Check if email is being changed and if it already exists
    if ($email) {
        $existing = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $userId")->fetch_assoc();
        if ($existing) {
            APIResponse::error('Email already exists');
            exit;
        }
    }
    
    // Update user
    $sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', address = '$address' WHERE id = $userId";
    
    if ($conn->query($sql)) {
        $updatedUser = $conn->query("SELECT id, name, email, role, phone, address FROM users WHERE id = $userId")->fetch_assoc();
        APIResponse::success($updatedUser, 'Profile updated successfully');
    } else {
        APIResponse::error('Failed to update profile: ' . $conn->error);
    }
    
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
