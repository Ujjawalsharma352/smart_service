<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    APIAuth::requireRole('admin');
    
    // Get system statistics
    $stats = [
        'users' => [
            'total' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'],
            'new_this_month' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user' AND MONTH(created_at) = MONTH(CURRENT_DATE)")->fetch_assoc()['count']
        ],
        'providers' => [
            'total' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'")->fetch_assoc()['count'],
            'new_this_month' => $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider' AND MONTH(created_at) = MONTH(CURRENT_DATE)")->fetch_assoc()['count']
        ],
        'services' => [
            'total' => $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'],
            'active' => $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch_assoc()['count'],
            'new_this_month' => $conn->query("SELECT COUNT(*) as count FROM services WHERE MONTH(created_at) = MONTH(CURRENT_DATE)")->fetch_assoc()['count']
        ],
        'bookings' => [
            'total' => $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'],
            'pending' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'],
            'accepted' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'accepted'")->fetch_assoc()['count'],
            'completed' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'")->fetch_assoc()['count'],
            'new_this_month' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE MONTH(created_at) = MONTH(CURRENT_DATE)")->fetch_assoc()['count']
        ],
        'revenue' => [
            'total' => $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.status = 'completed'")->fetch_assoc()['total'] ?? 0,
            'this_month' => $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.status = 'completed' AND MONTH(b.created_at) = MONTH(CURRENT_DATE)")->fetch_assoc()['total'] ?? 0
        ]
    ];
    
    // Get recent activity
    $recentBookings = $conn->query("
        SELECT b.*, u.name as user_name, p.name as provider_name, s.title as service_title
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN users p ON b.provider_id = p.id
        JOIN services s ON b.service_id = s.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    
    $recentActivity = [];
    while ($booking = $recentBookings->fetch_assoc()) {
        $recentActivity[] = [
            'id' => (int)$booking['id'],
            'user_name' => $booking['user_name'],
            'provider_name' => $booking['provider_name'],
            'service_title' => $booking['service_title'],
            'status' => $booking['status'],
            'created_at' => $booking['created_at']
        ];
    }
    
    APIResponse::success([
        'statistics' => $stats,
        'recent_activity' => $recentActivity
    ]);
    
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
