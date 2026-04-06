<?php
require_once '../config/db.php';
require_once '../notifications/notification_functions.php';
require_once '../notifications/language_helper.php';
requireRole('admin');

// Get dashboard statistics
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
$totalProviders = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'")->fetch_assoc()['count'];
$totalServices = $conn->query("SELECT COUNT(*) as count FROM services")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$pendingBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'pending'")->fetch_assoc()['count'];
$completedBookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE status = 'completed'")->fetch_assoc()['count'];

// Get admin user ID for notifications
$userId = getUserId();

// Load language dynamically
$lang_code = $_SESSION['lang'] ?? 'en';
$lang_file = "../lang/$lang_code.php";
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    require_once '../lang/en.php';
}

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
<html lang="<?php echo $lang_code; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $userId; ?>">
    <meta name="user-role" content="admin">
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
                <li><a href="manage_users.php"><?php echo $lang['users']; ?></a></li>
                <li><a href="manage_providers.php"><?php echo $lang['providers']; ?></a></li>
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
                
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php" class="active">📊 <?php echo $lang['dashboard']; ?></a></li>
                <li><a href="manage_users.php">👥 <?php echo $lang['manage_users']; ?></a></li>
                <li><a href="manage_providers.php">👨‍🔧 <?php echo $lang['manage_providers']; ?></a></li>
                <li><a href="manage_bookings.php">📅 <?php echo $lang['manage_bookings']; ?></a></li>
                <li><a href="../auth/logout.php">🚪 <?php echo $lang['logout']; ?></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $lang['dashboard']; ?></h1>
                <p class="page-subtitle"><?php echo $lang['welcome']; ?>, <?php echo $_SESSION['user_name']; ?>! <?php echo $lang['admin_subtitle']; ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalUsers; ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['users']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalProviders; ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['providers']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalServices; ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['services']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['bookings']; ?></div>
                </div>
            </div>

            <div class="stats-grid">
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $completedBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['completed']; ?> <?php echo $lang['jobs']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $completionRate = $totalBookings > 0 ? round(($completedBookings / $totalBookings) * 100, 1) : 0;
                        echo $completionRate . '%';
                        ?>
                    </div>
                    <div class="stat-label"><?php echo $lang['completion']; ?> <?php echo $lang['rate']; ?></div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $pendingBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['pending']; ?> <?php echo $lang['bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $avgCompletionTime = 7; // Average completion time in days
                        echo $avgCompletionTime . ' days';
                        ?>
                    </div>
                    <div class="stat-label"><?php echo $lang['average']; ?> <?php echo $lang['completion']; ?></div>
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
                                        <th><?php echo $lang['provider']; ?></th>
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
                                                        <div class="service-category"><?php echo htmlspecialchars($booking['service_title']); ?></div>
                                                    </div>
                                                </td>
                                                <td><?php echo htmlspecialchars($booking['service_title']); ?></td>
                                                <td><?php echo htmlspecialchars($booking['provider_name']); ?></td>
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
                                            <td colspan="5" class="text-center text-secondary">
                                                <?php echo $lang['no_bookings']; ?>
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

                <!-- Top Providers -->
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $lang['top']; ?> <?php echo $lang['providers']; ?></h3>
                        <a href="manage_providers.php" class="btn btn-primary btn-sm"><?php echo $lang['view_all']; ?></a>
                    </div>
                    <div class="card-body">
                        <?php if ($topProviders->num_rows > 0): ?>
                            <div class="space-y-4">
                                <?php while ($provider = $topProviders->fetch_assoc()): ?>
                                    <div class="service-card-small">
                                        <div class="flex justify-between items-start">
                                            <div class="flex-1">
                                                <h5 class="font-medium"><?php echo htmlspecialchars($provider['name']); ?></h5>
                                                <div class="service-category"><?php echo htmlspecialchars($provider['email']); ?></div>
                                                <div class="text-sm text-secondary mt-1">
                                                    <?php echo $provider['booking_count']; ?> <?php echo $lang['bookings']; ?>
                                                </div>
                                            </div>
                                            <div class="text-right">
                                                <div class="service-price">
                                                    <?php 
                                                    $avgRating = $conn->query("SELECT AVG(rating) as avg FROM reviews WHERE provider_id = " . $provider['id'])->fetch_assoc()['avg'] ?? 0;
                                                    if ($avgRating > 0) {
                                                        echo ' ' . number_format($avgRating, 1);
                                                    } else {
                                                        echo $lang['no_rating'];
                                                    }
                                                    ?>
                                                </div>
                                                <a href="manage_providers.php?edit=<?php echo $provider['id']; ?>" class="btn btn-primary btn-sm mt-2"><?php echo $lang['view']; ?></a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-secondary"><?php echo $lang['no_providers']; ?></p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="manage_providers.php" class="btn btn-secondary">Manage Providers</a>
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
                        <a href="manage_users.php" class="btn btn-primary"><?php echo $lang['manage_users']; ?></a>
                        <a href="manage_providers.php" class="btn btn-success"><?php echo $lang['manage_providers']; ?></a>
                        <a href="manage_bookings.php" class="btn btn-secondary"><?php echo $lang['manage_bookings']; ?></a>
                    </div>
                </div>
            </div>

            <!-- Pending Bookings Alert -->
            <?php if ($pendingBookings > 0): ?>
                <div class="alert alert-warning mt-8">
                    <strong> You have <?php echo $pendingBookings; ?> <?php echo $lang['pending']; ?> <?php echo $lang['bookings']; ?>!</strong> 
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
