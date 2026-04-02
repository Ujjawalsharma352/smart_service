<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    APIAuth::requireAuth();
    
    $userId = getUserId();
    $userRole = getUserRole();
    $status = APIInput::get('status', '');
    $limit = (int)APIInput::get('limit', 50);
    $offset = (int)APIInput::get('offset', 0);
    
    // Build query based on user role
    if ($userRole === 'user') {
        $whereClause = "WHERE b.user_id = $userId";
    } elseif ($userRole === 'provider') {
        $whereClause = "WHERE b.provider_id = $userId";
    } else {
        APIResponse::forbidden();
        exit;
    }
    
    if ($status) {
        $statusTerm = $conn->real_escape_string($status);
        $whereClause .= " AND b.status = '$statusTerm'";
    }
    
    // Get bookings
    $sql = "SELECT b.*, s.title as service_title, s.price as service_price, s.category,
                   u.name as other_name, u.email as other_email, u.phone as other_phone
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN users u ON " . ($userRole === 'user' ? 'b.provider_id' : 'b.user_id') . " = u.id
            $whereClause
            ORDER BY b.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $bookings = $conn->query($sql);
    
    $result = [];
    while ($booking = $bookings->fetch_assoc()) {
        $result[] = [
            'id' => (int)$booking['id'],
            'service' => [
                'id' => (int)$booking['service_id'],
                'title' => $booking['service_title'],
                'price' => (float)$booking['service_price'],
                'category' => $booking['category']
            ],
            'other_party' => [
                'name' => $booking['other_name'],
                'email' => $booking['other_email'],
                'phone' => $booking['other_phone']
            ],
            'booking_date' => $booking['booking_date'],
            'time_slot' => $booking['time_slot'],
            'address' => $booking['address'],
            'status' => $booking['status'],
            'created_at' => $booking['created_at']
        ];
    }
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM bookings b $whereClause";
    $total = $conn->query($countSql)->fetch_assoc()['total'];
    
    APIResponse::success([
        'bookings' => $result,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'has_more' => ($offset + $limit) < $total
        ]
    ]);
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
