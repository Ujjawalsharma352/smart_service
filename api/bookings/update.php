<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    APIAuth::requireAuth();
    
    $data = APIInput::getJSON();
    
    if (!APIInput::validate($data, ['booking_id', 'action'])) {
        exit;
    }
    
    $userId = getUserId();
    $userRole = getUserRole();
    $bookingId = (int)$data['booking_id'];
    $action = clean_input($data['action']);
    
    // Get booking details
    $booking = $conn->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch_assoc();
    
    if (!$booking) {
        APIResponse::notFound();
        exit;
    }
    
    // Check permissions
    if ($userRole === 'user' && $booking['user_id'] != $userId) {
        APIResponse::forbidden();
        exit;
    }
    
    if ($userRole === 'provider' && $booking['provider_id'] != $userId) {
        APIResponse::forbidden();
        exit;
    }
    
    // Handle different actions
    switch ($action) {
        case 'accept':
            if ($userRole !== 'provider') {
                APIResponse::forbidden();
                exit;
            }
            if ($booking['status'] !== 'pending') {
                APIResponse::error('Can only accept pending bookings');
                exit;
            }
            $newStatus = 'accepted';
            $message = 'Booking accepted';
            break;
            
        case 'reject':
            if ($userRole !== 'provider') {
                APIResponse::forbidden();
                exit;
            }
            if ($booking['status'] !== 'pending') {
                APIResponse::error('Can only reject pending bookings');
                exit;
            }
            $newStatus = 'rejected';
            $message = 'Booking rejected';
            break;
            
        case 'complete':
            if ($userRole !== 'provider') {
                APIResponse::forbidden();
                exit;
            }
            if ($booking['status'] !== 'accepted') {
                APIResponse::error('Can only complete accepted bookings');
                exit;
            }
            $newStatus = 'completed';
            $message = 'Booking marked as completed';
            break;
            
        case 'cancel':
            if ($userRole === 'user' && !in_array($booking['status'], ['pending', 'accepted'])) {
                APIResponse::error('Can only cancel pending or accepted bookings');
                exit;
            }
            if ($userRole === 'provider' && $booking['status'] !== 'pending') {
                APIResponse::error('Providers can only cancel pending bookings');
                exit;
            }
            $newStatus = 'cancelled';
            $message = 'Booking cancelled';
            break;
            
        default:
            APIResponse::error('Invalid action');
            exit;
    }
    
    // Update booking
    if ($conn->query("UPDATE bookings SET status = '$newStatus' WHERE id = $bookingId")) {
        APIResponse::success([
            'booking_id' => $bookingId,
            'new_status' => $newStatus
        ], $message);
    } else {
        APIResponse::error('Failed to update booking: ' . $conn->error);
    }
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
