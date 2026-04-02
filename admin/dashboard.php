<?php
require_once '../config/db.php';
requireRole('admin');

// Get dashboard statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$totalProviders = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'")->fetch_assoc()['count'];
$totalServices = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$pendingBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$completedBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'")->fetch_assoc()['count'];

// Get recent bookings
$recentBookings = $conn->query("
    SELECT b.*, u.name as user_name, p.name as provider_name, s.title as service_title
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN users p ON b.provider_id = p.id
    JOIN services s ON b.service_id = s.id
    ORDER BY b.created_at DESC
    LIMIT 5
");

// Get top providers
$topProviders = $conn->query("
    SELECT u.*, COUNT(b.id) as booking_count
    FROM users u
    LEFT JOIN bookings b ON u.id = b.provider_id
    WHERE u.role = 'provider'
    GROUP BY u.id
    ORDER BY booking_count DESC
    LIMIT 5
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Smart Service Finder</title>
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
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_providers.php">Providers</a></li>
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
                <li><a href="manage_users.php">👥 Users</a></li>
                <li><a href="manage_providers.php">🔧 Providers</a></li>
                <li><a href="manage_bookings.php">📅 Bookings</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Admin Dashboard</h1>
                <p class="page-subtitle">Welcome back, <?php echo $_SESSION['user_name']; ?>! Manage your service platform efficiently.</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $totalProviders; ?></div>
                    <div class="stat-label">Service Providers</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $totalServices; ?></div>
                    <div class="stat-label">Services</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                    <div class="stat-label">Total Bookings</div>
                </div>
            </div>

            <!-- Booking Statistics -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $pendingBookings; ?></div>
                    <div class="stat-label">Pending Bookings</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $completedBookings; ?></div>
                    <div class="stat-label">Completed Bookings</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round(($completedBookings / $totalBookings) * 100, 1); ?>%</div>
                    <div class="stat-label">Completion Rate</div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <!-- Recent Bookings -->
                <div class="card">
                    <div class="card-header">
                        <h3>Recent Bookings</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Service</th>
                                        <th>Status</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($booking['user_name']); ?></td>
                                            <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                            <td>
                                                <span class="badge badge-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Providers -->
                <div class="card">
                    <div class="card-header">
                        <h3>Top Providers</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Provider</th>
                                        <th>Email</th>
                                        <th>Bookings</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($provider = $topProviders->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($provider['name']); ?></td>
                                            <td><?php echo htmlspecialchars($provider['email']); ?></td>
                                            <td><?php echo $provider['booking_count']; ?></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
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
                        <a href="manage_users.php" class="btn btn-primary">Manage Users</a>
                        <a href="manage_providers.php" class="btn btn-secondary">Manage Providers</a>
                        <a href="manage_bookings.php" class="btn btn-success">View All Bookings</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
