<?php
require_once '../config/db.php';
requireRole('provider');

$providerId = getUserId();

// Get provider statistics
$totalServices = $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $providerId")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $providerId")->fetch_assoc()['count'];
$pendingBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $providerId AND status = 'pending'")->fetch_assoc()['count'];
$completedBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $providerId AND status = 'completed'")->fetch_assoc()['count'];
$totalEarnings = $conn->query("SELECT SUM(s.price) as total FROM bookings b JOIN services s ON b.service_id = s.id WHERE b.provider_id = $providerId AND b.status = 'completed'")->fetch_assoc()['total'] ?? 0;
$averageRating = $conn->query("SELECT AVG(rating) as avg FROM reviews WHERE provider_id = $providerId")->fetch_assoc()['avg'] ?? 0;

// Get recent bookings
$recentBookings = $conn->query("
    SELECT b.*, u.name as user_name, u.email as user_email, s.title as service_title, s.price
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE b.provider_id = $providerId
    ORDER BY b.created_at DESC
    LIMIT 5
");

// Get my services
$myServices = $conn->query("
    SELECT s.*, COUNT(b.id) as booking_count
    FROM services s
    LEFT JOIN bookings b ON s.id = b.service_id
    WHERE s.provider_id = $providerId
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Provider Dashboard - Smart Service Finder</title>
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
                <li><a href="add_service.php">Add Service</a></li>
                <li><a href="manage_bookings.php">Bookings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active">📊 Dashboard</a></li>
                <li><a href="add_service.php">➕ Add Service</a></li>
                <li><a href="manage_bookings.php">📅 Manage Bookings</a></li>
                <li><a href="my_services.php">🔧 My Services</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Provider Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo $_SESSION['user_name']; ?>! Manage your services and track your earnings.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalServices; ?></div>
                    <div class="stat-label">My Services</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $pendingBookings; ?></div>
                    <div class="stat-label">Pending Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($totalEarnings, 2); ?></div>
                    <div class="stat-label">Total Earnings</div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $completedBookings; ?></div>
                    <div class="stat-label">Completed Jobs</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php if ($averageRating > 0): ?>
                            <div class="flex items-center gap-1">
                                <span class="star">★</span>
                                <span><?php echo number_format($averageRating, 1); ?></span>
                            </div>
                        <?php else: ?>
                            <span class="text-light">No rating</span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-label">Average Rating</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round(($completedBookings / max($totalBookings, 1)) * 100, 1); ?>%</div>
                    <div class="stat-label">Completion Rate</div>
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
                                        <th>Customer</th>
                                        <th>Service</th>
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
                                                        <div class="font-medium"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                                        <div class="text-sm text-secondary"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
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
                                <a href="manage_bookings.php" class="btn btn-primary">View All Bookings</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Services -->
                <div class="card">
                    <div class="card-header">
                        <h3>My Services</h3>
                        <a href="add_service.php" class="btn btn-primary btn-sm">Add New</a>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Service</th>
                                        <th>Price</th>
                                        <th>Bookings</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if ($myServices->num_rows > 0): ?>
                                        <?php while ($service = $myServices->fetch_assoc()): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <div class="font-medium"><?php echo htmlspecialchars($service['title']); ?></div>
                                                        <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>
                                                    </div>
                                                </td>
                                                <td>$<?php echo number_format($service['price'], 2); ?></td>
                                                <td><?php echo $service['booking_count']; ?></td>
                                                <td>
                                                    <span class="badge badge-<?php echo $service['status']; ?>">
                                                        <?php echo ucfirst($service['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-secondary">
                                                No services added yet
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php if ($myServices->num_rows > 0): ?>
                            <div class="card-footer">
                                <a href="my_services.php" class="btn btn-secondary">Manage Services</a>
                            </div>
                        <?php endif; ?>
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
                        <a href="add_service.php" class="btn btn-primary">Add New Service</a>
                        <a href="manage_bookings.php" class="btn btn-success">Manage Bookings</a>
                        <a href="my_services.php" class="btn btn-secondary">View All Services</a>
                    </div>
                </div>
            </div>

            <!-- Pending Bookings Alert -->
            <?php if ($pendingBookings > 0): ?>
                <div class="alert alert-warning mt-8">
                    <strong>⚠️ You have <?php echo $pendingBookings; ?> pending booking(s)!</strong> 
                    Please review and respond to them promptly. 
                    <a href="manage_bookings.php" class="btn btn-warning btn-sm ml-4">View Bookings</a>
                </div>
            <?php endif; ?>
        </main>
    </div>

</body>
</html>
