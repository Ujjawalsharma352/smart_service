<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $search = APIInput::get('search', '');
    $category = APIInput::get('category', '');
    $limit = (int)APIInput::get('limit', 50);
    $offset = (int)APIInput::get('offset', 0);
    
    // Build query
    $whereClause = "WHERE s.status = 'active'";
    $params = [];
    
    if ($search) {
        $searchTerm = $conn->real_escape_string($search);
        $whereClause .= " AND (s.title LIKE '%$searchTerm%' OR s.description LIKE '%$searchTerm%' OR s.category LIKE '%$searchTerm%')";
    }
    
    if ($category) {
        $categoryTerm = $conn->real_escape_string($category);
        $whereClause .= " AND s.category = '$categoryTerm'";
    }
    
    // Get services
    $sql = "SELECT s.*, u.name as provider_name, u.email as provider_email, u.phone as provider_phone,
                   AVG(r.rating) as average_rating, COUNT(r.id) as review_count
            FROM services s
            JOIN users u ON s.provider_id = u.id
            LEFT JOIN reviews r ON u.id = r.provider_id
            $whereClause
            GROUP BY s.id
            ORDER BY s.created_at DESC
            LIMIT $limit OFFSET $offset";
    
    $services = $conn->query($sql);
    
    $result = [];
    while ($service = $services->fetch_assoc()) {
        $result[] = [
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
                'phone' => $service['provider_phone']
            ],
            'rating' => [
                'average' => (float)($service['average_rating'] ?? 0),
                'count' => (int)$service['review_count']
            ]
        ];
    }
    
    // Get total count
    $countSql = "SELECT COUNT(DISTINCT s.id) as total FROM services s 
                 JOIN users u ON s.provider_id = u.id 
                 $whereClause";
    $total = $conn->query($countSql)->fetch_assoc()['total'];
    
    APIResponse::success([
        'services' => $result,
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
