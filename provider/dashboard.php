<?php
require_once '../config/db.php';
require_once '../notifications/notification_functions.php';
require_once '../notifications/language_helper.php';
requireRole('provider');

$providerId = getUserId();

// Load language dynamically
$lang_code = $_SESSION['lang'] ?? 'en';
$lang_file = "../lang/$lang_code.php";
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    require_once '../lang/en.php';
}

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
<html lang="<?php echo $lang_code; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $providerId; ?>">
    <meta name="user-role" content="provider">
    <title><?php echo $lang['dashboard']; ?> - Smart Service Finder</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                <li><a href="dashboard.php" class="active"><?php echo $lang['dashboard']; ?></a></li>
                <li><a href="add_service.php"><?php echo $lang['add_service']; ?></a></li>
                <li><a href="manage_bookings.php"><?php echo $lang['bookings']; ?></a></li>
                
                <!-- Language Switcher -->
                <li class="language-switcher">
                    <form method="post" action="../notifications/language_helper.php" style="margin: 0;">
                        <select name="lang" class="form-select" onchange="this.form.submit()">
                            <option value="en" <?php echo $lang_code === 'en' ? 'selected' : ''; ?>><?php echo $lang['english']; ?></option>
                            <option value="hi" <?php echo $lang_code === 'hi' ? 'selected' : ''; ?>><?php echo $lang['hindi']; ?></option>
                        </select>
                    </form>
                </li>
                
                <!-- Notification Box -->
                <li class="notification-wrapper">
                    <button type="button" class="notification-button" id="notification-bell">
                        <i class="fas fa-bell"></i>
                        <?php echo $lang['notifications']; ?>
                        <span class="notification-badge" id="notification-badge" style="display: none;">0</span>
                    </button>
                    <div class="notification-dropdown" id="notification-dropdown">
                        <div class="notification-header">
                            <h3><?php echo $lang['notifications']; ?></h3>
                            <button type="button" class="notification-close" id="notification-close">×</button>
                        </div>
                        <div class="notification-list" id="notification-list">
                            <div class="loading"><?php echo $lang['loading']; ?></div>
                        </div>
                    </div>
                </li>
                
                <li><a href="../auth/logout.php"><?php echo $lang['logout']; ?></a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active"><?php echo $lang['dashboard']; ?></a></li>
                <li><a href="add_service.php"><?php echo $lang['add_service']; ?></a></li>
                <li><a href="manage_bookings.php"><?php echo $lang['manage_bookings']; ?></a></li>
                <li><a href="my_services.php"><?php echo $lang['my_services']; ?></a></li>
                <li><a href="profile.php"><?php echo $lang['profile']; ?></a></li>
                <li><a href="../auth/logout.php"><?php echo $lang['logout']; ?></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $lang['dashboard']; ?></h1>
                <p class="page-subtitle"><?php echo $lang['welcome']; ?>, <?php echo $_SESSION['user_name']; ?>! <?php echo $lang['provider_subtitle']; ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalServices; ?></div>
                    <div class="stat-label"><?php echo $lang['my_services']; ?></div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['bookings']; ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $pendingBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['pending']; ?> <?php echo $lang['bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo number_format($totalEarnings, 2); ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['earnings']; ?></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $completedBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['completed']; ?> <?php echo $lang['jobs']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php if ($averageRating > 0): ?>
                            <div class="flex items-center gap-1">
                                <span class="star">★</span>
                                <span><?php echo number_format($averageRating, 1); ?></span>
                            </div>
                        <?php else: ?>
                            <span class="text-light"><?php echo $lang['no_rating']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-label"><?php echo $lang['average']; ?> <?php echo $lang['rating']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round(($completedBookings / max($totalBookings, 1)) * 100, 1); ?>%</div>
                    <div class="stat-label"><?php echo $lang['completion']; ?> <?php echo $lang['rate']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">$<?php echo $totalBookings > 0 ? number_format($totalEarnings / $totalBookings, 2) : '0.00'; ?></div>
                    <div class="stat-label"><?php echo $lang['average']; ?> <?php echo $lang['earning']; ?></div>
                </div>
            </div>

            <div class="grid grid-cols-2 gap-8">
                <!-- Recent Bookings -->
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $lang['recent']; ?> <?php echo $lang['bookings']; ?></h3>
                        <?php if ($pendingBookings > 0): ?>
                            <span class="badge badge-warning"><?php echo $pendingBookings; ?> <?php echo $lang['pending']; ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th><?php echo $lang['customer']; ?></th>
                                        <th><?php echo $lang['service']; ?></th>
                                        <th><?php echo $lang['status']; ?></th>
                                        <th><?php echo $lang['date']; ?></th>
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
                                                <td><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></td>
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
                                <a href="manage_bookings.php" class="btn btn-primary"><?php echo $lang['view_details']; ?></a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- My Services -->
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $lang['my_services']; ?></h3>
                        <a href="add_service.php" class="btn btn-primary btn-sm"><?php echo $lang['add']; ?></a>
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
                    <h3><?php echo $lang['quick_actions']; ?></h3>
                </div>
                <div class="card-body">
                    <div class="flex gap-4">
                        <a href="add_service.php" class="btn btn-primary"><?php echo $lang['add_service']; ?></a>
                        <a href="manage_bookings.php" class="btn btn-success"><?php echo $lang['manage_bookings']; ?></a>
                        <a href="my_services.php" class="btn btn-secondary"><?php echo $lang['my_services']; ?></a>
                    </div>
                </div>
            </div>

            <!-- Pending Bookings Alert -->
            <?php if ($pendingBookings > 0): ?>
                <div class="alert alert-warning mt-8">
                    <strong>⚠️ You have <?php echo $pendingBookings; ?> <?php echo $lang['pending']; ?> <?php echo $lang['bookings']; ?>!</strong> 
                    <?php echo $lang['pending_bookings_alert']; ?>
                    <a href="manage_bookings.php" class="btn btn-warning btn-sm ml-4"><?php echo $lang['view_bookings']; ?></a>
                </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Language JavaScript -->
    <script>
        // Make language available globally
        window.lang = <?php echo json_encode($lang); ?>;
        
        // Initialize notification system
        document.addEventListener('DOMContentLoaded', function() {
            if (window.NotificationSystem) {
                window.notificationSystem = new NotificationSystem();
            }
        });

        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('language-dropdown');
            const switcher = document.querySelector('.language-switcher');
            
            if (dropdown && switcher && !switcher.contains(e.target)) {
                dropdown.classList.remove('show');
            }
        });
        
        // Mobile menu toggle
        function toggleMobileMenu() {
            const nav = document.querySelector('.navbar-nav');
            nav.classList.toggle('active');
        }
        
        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.querySelector('.navbar-nav');
            const toggle = document.querySelector('.mobile-menu-toggle');
            if (toggle && nav && !nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('active');
            }
        });
    </script>

    <!-- Notification System -->
    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
