<?php
require_once '../config.php';
require_once '../../notifications/notification_functions.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    APIAuth::requireAuth();
    
    if (getUserRole() !== 'user') {
        APIResponse::forbidden();
        exit;
    }
    
    $data = APIInput::getJSON();
    
    if (!APIInput::validate($data, ['service_id', 'booking_date', 'address'])) {
        exit;
    }
    
    $userId = getUserId();
    $serviceId = (int)$data['service_id'];
    $bookingDate = clean_input($data['booking_date']);
    $timeSlot = clean_input($data['time_slot'] ?? '');
    $address = clean_input($data['address']);
    
    // Validate date
    if (strtotime($bookingDate) < strtotime(date('Y-m-d'))) {
        APIResponse::error('Booking date cannot be in the past');
        exit;
    }
    
    // Get service details
    $service = $conn->query("SELECT * FROM services WHERE id = $serviceId AND status = 'active'")->fetch_assoc();
    
    if (!$service) {
        APIResponse::notFound();
        exit;
    }
    
    // Check for existing booking
    $existing = $conn->query("
        SELECT id FROM bookings 
        WHERE user_id = $userId AND service_id = $serviceId AND status IN ('pending', 'accepted')
    ")->fetch_assoc();
    
    if ($existing) {
        APIResponse::error('You already have a pending or accepted booking for this service');
        exit;
    }
    
    // Create booking
    $sql = "INSERT INTO bookings (user_id, provider_id, service_id, booking_date, time_slot, address, status) 
            VALUES ($userId, {$service['provider_id']}, $serviceId, '$bookingDate', '$timeSlot', '$address', 'pending')";
    
    if ($conn->query($sql)) {
        $bookingId = $conn->insert_id;
        createBookingNotifications($bookingId, $userId, $service['provider_id'], $service['title'], 'pending');
        
        // Get created booking details
        $booking = $conn->query("
            SELECT b.*, s.title as service_title, s.price as service_price,
                   u.name as provider_name, u.email as provider_email
            FROM bookings b
            JOIN services s ON b.service_id = s.id
            JOIN users u ON b.provider_id = u.id
            WHERE b.id = $bookingId
        ")->fetch_assoc();
        
        $result = [
            'id' => (int)$booking['id'],
            'service' => [
                'id' => (int)$booking['service_id'],
                'title' => $booking['service_title'],
                'price' => (float)$booking['service_price']
            ],
            'provider' => [
                'id' => (int)$booking['provider_id'],
                'name' => $booking['provider_name'],
                'email' => $booking['provider_email']
            ],
            'booking_date' => $booking['booking_date'],
            'time_slot' => $booking['time_slot'],
            'address' => $booking['address'],
            'status' => $booking['status'],
            'created_at' => $booking['created_at']
        ];
        
        APIResponse::success($result, 'Booking created successfully');
    } else {
        APIResponse::error('Failed to create booking: ' . $conn->error);
    }
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
