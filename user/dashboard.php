<?php
require_once '../config/db.php';
requireRole('user');

$userId = getUserId();

// Get user statistics
$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId")->fetch_assoc()['count'];
$pendingBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId AND status = 'pending'")->fetch_assoc()['count'];
$completedBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId AND status = 'completed'")->fetch_assoc()['count'];
$totalSpent = $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.user_id = $userId AND b.status = 'completed'")->fetch_assoc()['total'] ?? 0;

// Get recent bookings
$recentBookings = $conn->query("
    SELECT b.*, p.name as provider_name, s.title as service_title, s.price, s.category
    FROM bookings b
    JOIN users p ON b.provider_id = p.id
    JOIN services s ON b.service_id = s.id
    WHERE b.user_id = $userId
    ORDER BY b.created_at DESC  
    LIMIT 5
");

// Get available services count
$availableServices = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch_assoc()['count'];

// Get recommended services (random selection)
$recommendedServices = $conn->query("
    SELECT s.*, p.name as provider_name, AVG(r.rating) as average_rating
    FROM services s
    JOIN users p ON s.provider_id = p.id
    LEFT JOIN reviews r ON p.id = r.provider_id
    WHERE s.status = 'active'
    GROUP BY s.id
    ORDER BY RAND()
    LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Smart Service Finder</title>
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
                <li><a href="dashboard.php" class="active">Dashboard</a></li>
                <li><a href="services.php">Services</a></li>
                <li><a href="my_bookings.php">My Bookings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="services.php">🔧 Browse Services</a></li>
                <li><a href="my_bookings.php">📅 My Bookings</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">User Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo $_SESSION['user_name']; ?>! Here's your service activity overview.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $pendingBookings; ?></div>
                    <div class="stat-label">Pending Bookings</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $completedBookings; ?></div>
                    <div class="stat-label">Completed Services</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($totalSpent, 2); ?></div>
                    <div class="stat-label">Total Spent</div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $availableServices; ?></div>
                    <div class="stat-label">Available Services</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round(($completedBookings / max($totalBookings, 1)) * 100, 1); ?>%</div>
                    <div class="stat-label">Service Completion</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php if ($totalBookings > 0): ?>
                            <?php echo number_format($totalSpent / $totalBookings, 2); ?>
                        <?php else: ?>
                            0.00
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">Avg. Booking Cost</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <!-- Recent Bookings -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Bookings</h3>
                        <?php if ($pendingBookings > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingBookings; ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Provider</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($recentBookings->num_rows > 0): ?>
                                        <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div class="font-medium"><?php echo htmlspecialchars($booking['service_title']); ?></div>
                                                        <div class="service-category"><?php echo htmlspecialchars($booking['category']); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['provider_name']); ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $booking['status']; ?>">
                                                        <?php echo ucfirst($booking['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary">
                                                No bookings yet
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($recentBookings->num_rows > 0): ?>
                            <div class="card-footer">
                                <a href="my_bookings.php" class="btn btn-primary">View All Bookings</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recommended Services -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recommended Services</h3>
                        <a href="services.php" class="btn btn-primary btn-sm">Browse All</a>
                    </div>
                    <div class="card-body">
                        <?php if ($recommendedServices->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while ($service = $recommendedServices->fetch_assoc()): ?>
                                    <div class="service-card-small">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h5 class="font-medium"><?php echo htmlspecialchars($service['title']); ?></h5>
                                                <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>
                                                <div class="text-sm text-secondary mt-1">
                                                    by <?php echo htmlspecialchars($service['provider_name']); ?>
                                                </div>
                                                <?php if ($service['average_rating']): ?>
                                                    <div class="flex items-center gap-1 mt-1">
                                                        <span class="star">★</span>
                                                        <span class="text-sm"><?php echo number_format($service['average_rating'], 1); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="text-right">
                                                <div class="service-price">$<?php echo number_format($service['price'], 2); ?></div>
                                                <a href="book_service.php?id=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm mt-2">Book</a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-secondary text-center">No services available</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="services.php" class="btn btn-secondary">View All Services</a>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="card mt-8">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="flex gap-4">
                        <a href="services.php" class="btn btn-primary">Browse Services</a>
                        <a href="my_bookings.php" class="btn btn-success">View My Bookings</a>
                        <a href="profile.php" class="btn btn-secondary">Update Profile</a>
                    </div>
                </div>
            </div>

            <!-- Pending Bookings Alert -->
            <?php if ($pendingBookings > 0): ?>
                <div class="alert alert-warning mt-8">
                    <strong>⚠️ You have <?php echo $pendingBookings; ?> pending booking(s)!</strong> 
                    Track your booking status and communicate with service providers.
                    <a href="my_bookings.php" class="btn btn-warning btn-sm ml-4">View Bookings</a>
                </div>
            <?php endif; ?>

            <!-- Welcome Message for New Users -->
            <?php if ($totalBookings == 0): ?>
                <div class="alert alert-info mt-8">
                    <strong>👋 Welcome to Smart Service Finder!</strong> 
                    Start by browsing our available services and book your first service today.
                    <a href="services.php" class="btn btn-primary btn-sm ml-4">Browse Services</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <style>
        .space-y-4 > * + * {
            margin-top: 1rem;
        }
        
        .service-card-small {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            transition: all 0.2s ease;
        }
        
        .service-card-small:hover {
            box-shadow: var(--shadow-md);
            transform: translateY(-1px);
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
