<?php
require_once '../config/db.php';

// Check if user is logged in
if (!isLoggedIn()) {
    redirect('../auth/login.php');
}

requireRole('user');

$userId = getUserId();
$serviceId = (int)$_GET['id'];

// Get service details
$service = $conn->query("
    SELECT s.*, u.name as provider_name, u.email as provider_email, u.phone as provider_phone,
           AVG(r.rating) as average_rating
    FROM services s
    JOIN users u ON s.provider_id = u.id
    LEFT JOIN reviews r ON u.id = r.provider_id
    WHERE s.id = $serviceId AND s.status = 'active'
")->fetch_assoc();

if (!$service) {
    die("Service not found or not available.");
}

// Check if user already has a pending booking for this service
$existingBooking = $conn->query("
    SELECT id FROM bookings 
    WHERE user_id = $userId AND service_id = $serviceId AND status IN ('pending', 'accepted')
")->fetch_assoc();

$success = '';
$error = '';

// Handle booking submission - SIMPLIFIED
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("BOOKING FORM SUBMITTED - User: $userId, Service: $serviceId");
    
    $bookingDate = clean_input($_POST['booking_date']);
    $timeSlot = clean_input($_POST['time_slot']);
    $address = clean_input($_POST['address']);
    
    error_log("FORM DATA - Date: $bookingDate, Time: $timeSlot, Address: $address");
    
    // Simple validation
    if (empty($bookingDate) || empty($address)) {
        $error = "Please fill in all required fields";
        error_log("VALIDATION FAILED - Missing fields");
    } else {
        // Direct SQL insertion - NO prepared statements for now
        $sql = "INSERT INTO bookings (user_id, provider_id, service_id, booking_date, time_slot, address, status) 
                VALUES ($userId, {$service['provider_id']}, $serviceId, '$bookingDate', '$timeSlot', '$address', 'pending')";
        
        error_log("SQL QUERY: $sql");
        
        if ($conn->query($sql)) {
            $bookingId = $conn->insert_id;
            $success = "🎉 Booking successful! Your booking ID is: $bookingId";
            error_log("BOOKING SUCCESS - ID: $bookingId");
            
            // Verify booking was inserted
            $verify = $conn->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch_assoc();
            if ($verify) {
                error_log("BOOKING VERIFIED - Found in database");
            } else {
                error_log("BOOKING NOT VERIFIED - Not found in database");
            }
            
            // Clear form
            $_POST = array();
            
        } else {
            $error = "❌ Booking failed: " . $conn->error;
            error_log("BOOKING FAILED: " . $conn->error);
        }
    }
}

// Get provider's other services
$otherServices = $conn->query("
    SELECT * FROM services 
    WHERE provider_id = {$service['provider_id']} AND id != $serviceId AND status = 'active'
    ORDER BY created_at DESC
    LIMIT 3
");

// Get recent reviews
$reviews = $conn->query("
    SELECT r.*, u.name as reviewer_name
    FROM reviews r
    JOIN users u ON r.user_id = u.id
    WHERE r.provider_id = {$service['provider_id']}
    ORDER BY r.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Service - Smart Service Finder</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">
                🔧 Smart Service Finder
            </a>
            <ul class="navbar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="services.php" class="active">Services</a></li>
                <li><a href="my_bookings.php">My Bookings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="services.php" class="active">🔧 Browse Services</a></li>
                <li><a href="my_bookings.php">📅 My Bookings</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Book Service</h1>
                <p class="page-subtitle">Complete your booking request</p>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Booking Form -->
                <div class="col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h3>Service Details</h3>
                        </div>
                        <div class="card-body">
                            <div class="service-detail mb-6">
                                <div class="flex justify-between items-start mb-4">
                                    <div>
                                        <h2 class="text-xl font-bold"><?php echo htmlspecialchars($service['title']); ?></h2>
                                        <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>
                                    </div>
                                    <div class="text-right">
                                        <div class="service-price text-2xl">$<?php echo number_format($service['price'], 2); ?></div>
                                        <?php if ($service['average_rating']): ?>
                                            <div class="flex items-center gap-1">
                                                <span class="star">★</span>
                                                <span><?php echo number_format($service['average_rating'], 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <p><?php echo htmlspecialchars($service['description']); ?></p>
                                
                                <div class="service-provider mt-4">
                                    <div class="provider-avatar">
                                        <?php echo strtoupper(substr($service['provider_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="font-medium"><?php echo htmlspecialchars($service['provider_name']); ?></div>
                                        <div class="text-sm text-secondary">Professional Provider</div>
                                        <?php if ($service['provider_phone']): ?>
                                            <div class="text-sm text-secondary"><?php echo htmlspecialchars($service['provider_phone']); ?></div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <?php if ($existingBooking): ?>
                                <div class="alert alert-warning">
                                    <strong>⚠️ Pending Booking:</strong> You already have a booking request for this service that is being reviewed by the provider.
                                    <a href="my_bookings.php" class="btn btn-warning btn-sm ml-4">View Booking</a>
                                </div>
                            <?php else: ?>
                                <?php if ($error): ?>
                                    <div class="alert alert-error">
                                        <?php echo $error; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if ($success): ?>
                                    <div class="alert alert-success">
                                        <strong>✅ <?php echo $success; ?></strong>
                                        <div style="margin-top: 10px;">
                                            <a href="my_bookings.php" class="btn btn-success btn-sm">View My Bookings</a>
                                            <a href="book_service.php?id=<?php echo $serviceId; ?>" class="btn btn-secondary btn-sm">Book Another Service</a>
                                            <button onclick="location.reload()" class="btn btn-primary btn-sm">Refresh Page</button>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <form method="POST" action="">
                                    <div class="form-row">
                                        <div class="form-group">
                                            <label for="booking_date">Preferred Date *</label>
                                            <input type="date" id="booking_date" name="booking_date" required 
                                                   value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                                                   min="<?php echo date('Y-m-d'); ?>">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="time_slot">Preferred Time</label>
                                            <select id="time_slot" name="time_slot">
                                                <option value="">Select Time (Optional)</option>
                                                <option value="Morning (8AM-12PM)">Morning (8AM-12PM)</option>
                                                <option value="Afternoon (12PM-4PM)">Afternoon (12PM-4PM)</option>
                                                <option value="Evening (4PM-8PM)">Evening (4PM-8PM)</option>
                                                <option value="Flexible">Flexible</option>
                                            </select>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="address">Service Address *</label>
                                        <textarea id="address" name="address" required rows="3"
                                                  placeholder="Enter complete address where service should be provided...">123 Test Street</textarea>
                                    </div>
                                    
                                    <div class="form-group">
                                        <button type="submit" class="btn btn-primary">🚀 Book Service Now</button>
                                        <a href="services.php" class="btn btn-secondary">Cancel</a>
                                    </div>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Side Panel -->
                <div>
                    <!-- Provider Info -->
                    <div class="card">
                        <div class="card-header">
                            <h3>About Provider</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <div class="provider-avatar mx-auto mb-3" style="width: 60px; height: 60px; font-size: 1.5rem;">
                                    <?php echo strtoupper(substr($service['provider_name'], 0, 1)); ?>
                                </div>
                                <h4 class="font-medium"><?php echo htmlspecialchars($service['provider_name']); ?></h4>
                                <div class="text-secondary">Professional Service Provider</div>
                            </div>
                            
                            <?php if ($service['average_rating']): ?>
                                <div class="text-center mb-4">
                                    <div class="flex items-center justify-center gap-1">
                                        <span class="star">★</span>
                                        <span class="font-medium"><?php echo number_format($service['average_rating'], 1); ?></span>
                                    </div>
                                    <div class="text-sm text-secondary">
                                        <?php 
                                            $ratingCount = $conn->query("SELECT COUNT(*) as count FROM reviews WHERE provider_id = {$service['provider_id']}")->fetch_assoc()['count'];
                                            echo $ratingCount . ' review' . ($ratingCount != 1 ? 's' : '');
                                        ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <div class="space-y-2">
                                <?php if ($service['provider_email']): ?>
                                    <div class="flex items-center gap-2">
                                        <span>📧</span>
                                        <span class="text-sm"><?php echo htmlspecialchars($service['provider_email']); ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($service['provider_phone']): ?>
                                    <div class="flex items-center gap-2">
                                        <span>📱</span>
                                        <span class="text-sm"><?php echo htmlspecialchars($service['provider_phone']); ?></span>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Other Services -->
                    <?php if ($otherServices->num_rows > 0): ?>
                        <div class="card mt-6">
                            <div class="card-header">
                                <h3>Other Services</h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-4">
                                    <?php while ($otherService = $otherServices->fetch_assoc()): ?>
                                        <div class="border-bottom pb-3">
                                            <h5 class="font-medium"><?php echo htmlspecialchars($otherService['title']); ?></h5>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="service-category"><?php echo htmlspecialchars($otherService['category']); ?></span>
                                                <span class="service-price">$<?php echo number_format($otherService['price'], 2); ?></span>
                                            </div>
                                            <a href="book_service.php?id=<?php echo $otherService['id']; ?>" class="btn btn-primary btn-sm mt-2">Book</a>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Recent Reviews -->
                    <?php if ($reviews->num_rows > 0): ?>
                        <div class="card mt-6">
                            <div class="card-header">
                                <h3>Recent Reviews</h3>
                            </div>
                            <div class="card-body">
                                <div class="space-y-3">
                                    <?php while ($review = $reviews->fetch_assoc()): ?>
                                        <div class="border-bottom pb-3">
                                            <div class="flex justify-between items-start">
                                                <div>
                                                    <div class="font-medium"><?php echo htmlspecialchars($review['reviewer_name']); ?></div>
                                                    <div class="flex items-center gap-1">
                                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                                            <span class="star <?php echo $i <= $review['rating'] ? '' : 'empty'; ?>">★</span>
                                                        <?php endfor; ?>
                                                    </div>
                                                </div>
                                                <div class="text-sm text-secondary">
                                                    <?php echo date('M j, Y', strtotime($review['created_at'])); ?>
                                                </div>
                                            </div>
                                            <?php if ($review['comment']): ?>
                                                <p class="text-sm mt-2"><?php echo htmlspecialchars($review['comment']); ?></p>
                                            <?php endif; ?>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <style>
        .service-detail {
            padding: 1.5rem;
            background: var(--bg-secondary);
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }
        
        .space-y-2 > * + * {
            margin-top: 0.5rem;
        }
        
        .space-y-3 > * + * {
            margin-top: 0.75rem;
        }
        
        .border-bottom {
            border-bottom: 1px solid var(--border-color);
        }
        
        .text-xl {
            font-size: 1.25rem;
        }
        
        .text-2xl {
            font-size: 1.5rem;
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
