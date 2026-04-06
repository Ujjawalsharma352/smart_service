<?php
require_once '../config/db.php';
require_once '../notifications/notification_functions.php';
require_once '../notifications/language_helper.php';
requireRole('user');

$userId = getUserId();

// Load language dynamically
$lang_code = $_SESSION['lang'] ?? 'en';
$lang_file = "../lang/$lang_code.php";
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    require_once '../lang/en.php';
}

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
    LIMIT 3
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
<html lang="<?php echo $lang_code; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $userId; ?>">
    <meta name="user-role" content="user">
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
                <li><a href="services.php"><?php echo $lang['services']; ?></a></li>
                <li><a href="my_bookings.php"><?php echo $lang['bookings']; ?></a></li>
                <li><a href="profile.php"><?php echo $lang['profile']; ?></a></li>
                
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
                <li><a href="services.php">🔧 <?php echo $lang['services']; ?></a></li>
                <li><a href="my_bookings.php">📅 <?php echo $lang['bookings']; ?></a></li>
                <li><a href="profile.php">👤 <?php echo $lang['profile']; ?></a></li>
                <li><a href="../auth/logout.php">🚪 <?php echo $lang['logout']; ?></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $lang['dashboard']; ?></h1>
                <p class="page-subtitle"><?php echo $lang['welcome']; ?>, <?php echo $_SESSION['user_name']; ?>! <?php echo $lang['user_subtitle']; ?></p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['bookings']; ?></div>
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
                    <div class="stat-value">$<?php echo number_format($totalSpent, 2); ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['spent']; ?></div>
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
                <div class="stat-card">
                    <div class="stat-value"><?php echo $availableServices; ?></div>
                    <div class="stat-label"><?php echo $lang['available']; ?> <?php echo $lang['services']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-value">
                        <?php 
                        $avgSpent = $completedBookings > 0 ? number_format($totalSpent / $completedBookings, 2) : '0.00';
                        echo '$' . $avgSpent;
                        ?>
                    </div>
                    <div class="stat-label"><?php echo $lang['average']; ?> <?php echo $lang['cost']; ?></div>
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
                                <a href="my_bookings.php" class="btn btn-primary">View Details</a>
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
                            <p class="text-center text-secondary">No services available</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <a href="services.php" class="btn btn-secondary">Browse Services</a>
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
                        <a href="services.php" class="btn btn-primary"><?php echo $lang['search']; ?></a>
                        <a href="my_bookings.php" class="btn btn-success"><?php echo $lang['view_details']; ?></a>
                        <a href="profile.php" class="btn btn-secondary"><?php echo $lang['profile']; ?></a>
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
        
        /* Notification Bell & Language Switcher Styles */
        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }
        
        .navbar-brand {
            flex: 1;
        }
        
        .navbar-nav {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 0;
        }
        
        .navbar-nav li {
            position: relative;
        }
        
        .language-switcher select {
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            color: var(--text-primary);
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            font-size: 0.8rem;
            cursor: pointer;
            transition: all 0.3s ease;
            min-width: 120px;
        }
        
        .language-switcher select:hover {
            background: var(--bg-hover);
            border-color: var(--primary-color);
        }
        
        .notification-wrapper {
            position: relative;
            display: inline-block;
            z-index: 1001;
        }

        .notification-button {
            cursor: pointer;
            border: none;
            background: rgba(255, 255, 255, 0.95);
            color: #1f2937;
            font-size: 0.95rem;
            font-weight: 700;
            border-radius: 14px;
            padding: 0.75rem 1rem;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            transition: all 0.25s ease;
            box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        }

        .notification-button:hover {
            background: rgba(248, 250, 252, 0.95);
            transform: translateY(-1px);
            border-color: rgba(148, 163, 184, 0.55);
        }
        
        .notification-badge {
            position: absolute;
            top: -6px;
            right: -6px;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            border-radius: 50%;
            min-width: 18px;
            height: 18px;
            font-size: 0.65rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #ffffff;
            box-shadow: 0 3px 10px rgba(239, 68, 68, 0.4);
            animation: badgePulse 2s ease-in-out infinite;
        }
        
        .notification-dropdown {
            position: absolute;
            top: calc(100% + 12px);
            right: 0;
            left: auto;
            width: min(380px, calc(100vw - 32px));
            max-height: 420px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.25);
            z-index: 1001;
            display: none;
            overflow: hidden;
        }
        
        .notification-dropdown.show {
            display: block;
            animation: dropdownSlide 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.2rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: linear-gradient(135deg, rgba(96, 165, 250, 0.1), rgba(59, 130, 246, 0.05));
        }
        
        .notification-header h3 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #1f2937;
        }
        
        .notification-list {
            max-height: 320px;
            overflow-y: auto;
            padding: 0.5rem;
        }
        
        .notification-item {
            display: flex;
            align-items: flex-start;
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            border-radius: 12px;
            margin-bottom: 0.5rem;
        }
        
        .notification-item:hover {
            background: rgba(96, 165, 250, 0.05);
            transform: translateX(4px);
        }
        
        .notification-item.unread {
            background: rgba(96, 165, 250, 0.1);
            border-left: 3px solid #3b82f6;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.1);
        }
        
        .notification-item.read {
            opacity: 0.7;
        }
        
        .notification-content {
            flex: 1;
            margin-right: 0.5rem;
        }
        
        .notification-message {
            font-size: 0.8rem;
            color: var(--text-primary);
            margin-bottom: 0.25rem;
            line-height: 1.4;
        }
        
        .notification-time {
            font-size: 0.7rem;
            color: var(--text-light);
        }
        
        .notification-status {
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 20px;
        }
        
        .unread-dot {
            width: 8px;
            height: 8px;
            background: var(--primary-color);
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        
        /* Toast Notifications - TOP POSITION */
        .notification-toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 2000;
            pointer-events: none;
        }
        
        .notification-toast {
            position: relative;
            min-width: 300px;
            max-width: 400px;
            margin-bottom: 10px;
            transform: translateX(100%);
            transition: all 0.3s ease;
            pointer-events: auto;
            background: white;
            border-radius: var(--radius);
            box-shadow: var(--shadow-lg);
            border-left: 4px solid var(--primary-color);
        }
        
        .notification-toast.show {
            transform: translateX(0);
        }
        
        .notification-toast.hide {
            transform: translateX(100%);
            opacity: 0;
        }
        
        .toast-content {
            display: flex;
            align-items: center;
            padding: 1rem;
        }
        
        .toast-icon {
            font-size: 1.2rem;
            margin-right: 0.75rem;
            font-weight: bold;
            min-width: 24px;
            text-align: center;
        }
        
        .toast-message {
            flex: 1;
            font-size: 0.875rem;
            color: var(--text-primary);
            line-height: 1.4;
            font-weight: 500;
        }
        
        .toast-close {
            background: none;
            border: none;
            font-size: 1.2rem;
            color: var(--text-light);
            cursor: pointer;
            padding: 0;
            margin-left: 0.5rem;
            transition: var(--transition);
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            opacity: 0.7;
        }
        
        .toast-close:hover {
            color: var(--text-primary);
            opacity: 1;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(59, 130, 246, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(59, 130, 246, 0.7);
            }
        }
        
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .navbar-container {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .navbar-nav {
                width: 100%;
                flex-direction: column;
                gap: 0;
                display: none;
            }
            
            .navbar-nav.active {
                display: flex;
            }
            
            .notification-dropdown {
                width: calc(100vw - 40px);
                right: 20px;
            }
            
            .notification-toast-container {
                top: 10px;
                right: 10px;
                left: 10px;
            }
            
            .notification-toast {
                min-width: auto;
                max-width: none;
            }
        }
    </style>

    <script>
        // Global language object
        window.lang = <?php echo json_encode($lang); ?>;
        
        // Notification System
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize notification system
            if (window.NotificationSystem) {
                window.notificationSystem = new NotificationSystem();
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
            if (nav && toggle && !nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('active');
            }
        });
    </script>
    
    <!-- Notification System JavaScript -->
    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
