<?php
require_once '../config/db.php';
requireRole('admin');

$providerId = (int)$_GET['id'];

// Get provider details
$provider = $conn->query("
    SELECT u.*, 
           COUNT(s.id) as service_count,
           COUNT(b.id) as booking_count,
           AVG(r.rating) as average_rating
    FROM users u
    LEFT JOIN services s ON u.id = s.provider_id
    LEFT JOIN bookings b ON u.id = b.provider_id
    LEFT JOIN reviews r ON u.id = r.provider_id
    WHERE u.id = $providerId AND u.role = 'provider'
    GROUP BY u.id
")->fetch_assoc();

if (!$provider) {
    echo '<p>Provider not found</p>';
    exit;
}

// Get provider's services
$services = $conn->query("
    SELECT * FROM services 
    WHERE provider_id = $providerId 
    ORDER BY created_at DESC
");

// Get recent bookings
$recentBookings = $conn->query("
    SELECT b.*, u.name as user_name, s.title as service_title
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE b.provider_id = $providerId
    ORDER BY b.created_at DESC
    LIMIT 5
");

// Get reviews
$reviews = $conn->query("
    SELECT r.*, u.name as reviewer_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.provider_id = $providerId
    ORDER BY r.created_at DESC
    LIMIT 5
");
?>

<div class="provider-details">
    <div class="provider-header">
        <div class="provider-avatar" style="width: 80px; height: 80px; font-size: 2rem; margin: 0 auto 1rem;">
            <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
        </div>
        <h3><?php echo htmlspecialchars($provider['name']); ?></h3>
        <span class="badge badge-success">Provider</span>
    </div>
    
    <div class="provider-info">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <h4>Contact Information</h4>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($provider['email']); ?></p>
                <?php if ($provider['phone']): ?>
                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($provider['phone']); ?></p>
                <?php endif; ?>
                <?php if ($provider['address']): ?>
                    <p><strong>Address:</strong> <?php echo htmlspecialchars($provider['address']); ?></p>
                <?php endif; ?>
                <p><strong>Joined:</strong> <?php echo date('M j, Y', strtotime($provider['created_at'])); ?></p>
            </div>
            
            <div>
                <h4>Statistics</h4>
                <p><strong>Services:</strong> <?php echo $provider['service_count']; ?></p>
                <p><strong>Bookings:</strong> <?php echo $provider['booking_count']; ?></p>
                <?php if ($provider['average_rating']): ?>
                    <p><strong>Average Rating:</strong> 
                        <span class="star">★</span> 
                        <?php echo number_format($provider['average_rating'], 1); ?>
                    </p>
                <?php else: ?>
                    <p><strong>Average Rating:</strong> No ratings yet</p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php if ($services->num_rows > 0): ?>
        <div class="provider-services">
            <h4>Services (<?php echo $services->num_rows; ?>)</h4>
            <div class="space-y-2">
                <?php while ($service = $services->fetch_assoc()): ?>
                    <div class="border-bottom pb-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($service['title']); ?></strong>
                                <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>
                            </div>
                            <div class="text-right">
                                <div class="service-price">$<?php echo number_format($service['price'], 2); ?></div>
                                <span class="badge badge-<?php echo $service['status']; ?>">
                                    <?php echo ucfirst($service['status']); ?>
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($recentBookings->num_rows > 0): ?>
        <div class="provider-bookings">
            <h4>Recent Bookings</h4>
            <div class="space-y-2">
                <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                    <div class="border-bottom pb-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($booking['service_title']); ?></strong>
                                <div class="text-sm text-secondary">
                                    Customer: <?php echo htmlspecialchars($booking['user_name']); ?>
                                </div>
                            </div>
                            <span class="badge badge-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($reviews->num_rows > 0): ?>
        <div class="provider-reviews">
            <h4>Recent Reviews</h4>
            <div class="space-y-2">
                <?php while ($review = $reviews->fetch_assoc()): ?>
                    <div class="border-bottom pb-2">
                        <div class="flex justify-between items-start">
                            <div>
                                <strong><?php echo htmlspecialchars($review['reviewer_name']); ?></strong>
                                <div class="flex items-center gap-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <span class="star <?php echo $i <= $review['rating'] ? '' : 'empty'; ?>">★</span>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($review['comment']): ?>
                                    <p class="text-sm mt-1"><?php echo htmlspecialchars($review['comment']); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="text-sm text-secondary">
                                <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.provider-details {
    max-height: 500px;
    overflow-y: auto;
}

.provider-header {
    text-align: center;
    padding-bottom: 1rem;
    border-bottom: 1px solid var(--border-color);
    margin-bottom: 1rem;
}

.provider-info {
    margin-bottom: 1.5rem;
}

.provider-services,
.provider-bookings,
.provider-reviews {
    margin-bottom: 1.5rem;
}

.provider-services h4,
.provider-bookings h4,
.provider-reviews h4 {
    margin-bottom: 0.75rem;
    font-weight: 600;
}

.space-y-2 > * + * {
    margin-top: 0.5rem;
}

.border-bottom {
    border-bottom: 1px solid var(--border-color);
}
</style>
