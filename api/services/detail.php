<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $serviceId = (int)APIInput::get('id');
    
    if (!$serviceId) {
        APIResponse::error('Service ID is required');
        exit;
    }
    
    // Get service details
    $service = $conn->query("
        SELECT s.*, u.name as provider_name, u.email as provider_email, u.phone as provider_phone, u.address as provider_address,
               AVG(r.rating) as average_rating, COUNT(r.id) as review_count
        FROM services s
        JOIN users u ON s.provider_id = u.id
        LEFT JOIN reviews r ON u.id = r.provider_id
        WHERE s.id = $serviceId AND s.status = 'active'
        GROUP BY s.id
    ")->fetch_assoc();
    
    if (!$service) {
        APIResponse::notFound();
        exit;
    }
    
    // Get provider's other services
    $otherServices = $conn->query("
        SELECT id, title, price, category
        FROM services 
        WHERE provider_id = {$service['provider_id']} AND id != $serviceId AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    
    $otherServicesList = [];
    while ($other = $otherServices->fetch_assoc()) {
        $otherServicesList[] = [
            'id' => (int)$other['id'],
            'title' => $other['title'],
            'price' => (float)$other['price'],
            'category' => $other['category']
        ];
    }
    
    // Get recent reviews for this provider
    $reviews = $conn->query("
        SELECT r.*, u.name as user_name
        FROM reviews r
        JOIN users u ON r.user_id = u.id
        WHERE r.provider_id = {$service['provider_id']}
        ORDER BY r.created_at DESC
        LIMIT 5
    ");
    
    $reviewsList = [];
    while ($review = $reviews->fetch_assoc()) {
        $reviewsList[] = [
            'id' => (int)$review['id'],
            'user_name' => $review['user_name'],
            'rating' => (int)$review['rating'],
            'comment' => $review['comment'],
            'created_at' => $review['created_at']
        ];
    }
    
    $result = [
        'id' => (int)$service['id'],
        'title' => $service['title'],
        'description' => $service['description'],
        'price' => (float)$service['price'],
        'category' => $service['category'],
        'status' => $service['status'],
        'created_at' => $service['created_at'],
        'provider' => [
            'id' => (int)$service['provider_id'],
            'name' => $service['provider_name'],
            'email' => $service['provider_email'],
            'phone' => $service['provider_phone'],
            'address' => $service['provider_address']
        ],
        'rating' => [
            'average' => (float)($service['average_rating'] ?? 0),
            'count' => (int)$service['review_count']
        ],
        'other_services' => $otherServicesList,
        'recent_reviews' => $reviewsList
    ];
    
    APIResponse::success($result);
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
