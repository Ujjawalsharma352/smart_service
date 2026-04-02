<?php
require_once '../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $bookingId = clean_input($_GET['id'] ?? '');
    
    if (empty($bookingId) || !is_numeric($bookingId)) {
        APIResponse::error('Invalid booking ID', 400);
        exit;
    }
    
    // Get booking status
    $booking = $conn->query("SELECT status, updated_at FROM bookings WHERE id = $bookingId")->fetch_assoc();
    
    if ($booking) {
        APIResponse::success([
            'status' => $booking['status'],
            'updated_at' => $booking['updated_at']
        ], 'Status retrieved successfully');
    } else {
        APIResponse::error('Booking not found', 404);
    }
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
