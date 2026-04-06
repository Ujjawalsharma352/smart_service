<?php
require_once '../config/db.php';
require_once '../notifications/notification_functions.php';
require_once '../notifications/language_helper.php';
requireRole('user');

$userId = getUserId();

// Load language
$lang = loadLanguage();

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
    LEFT JOIN reviews r ON s.id = r.service_id
    WHERE s.status = 'active'
    GROUP BY s.id, p.name
    ORDER BY RAND()
    LIMIT 3
");
?>

<!DOCTYPE html>
<html lang="<?php echo getCurrentLanguage(); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="user-id" content="<?php echo $userId; ?>">
    <meta name="user-role" content="user">
    <title><?php echo $lang['dashboard']; ?> - Smart Service Finder</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="../assets/css/notifications.css">
    <style>
        .language-switcher {
            position: relative;
            margin-left: auto;
        }
        
        .language-current {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: var(--bg-secondary);
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            cursor: pointer;
            font-size: 0.875rem;
            transition: var(--transition);
        }
        
        .language-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            box-shadow: var(--shadow-md);
            min-width: 120px;
            z-index: 1000;
            display: none;
        }
        
        .language-dropdown.show {
            display: block;
        }
        
        .language-option {
            display: block;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
            text-decoration: none;
            transition: var(--transition);
            font-size: 0.875rem;
        }
        
        .language-option:hover {
            background: var(--bg-hover);
            color: var(--primary-color);
        }
        
        .language-option.active {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="../index.php" class="navbar-brand">
                🔧 Smart Service Finder
            </a>
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <ul class="navbar-nav">
                <li><a href="dashboard.php" class="active"><?php echo $lang['dashboard']; ?></a></li>
                <li><a href="services.php"><?php echo $lang['my_services']; ?></a></li>
                <li><a href="my_bookings.php"><?php echo $lang['bookings']; ?></a></li>
                <li><a href="profile.php"><?php echo $lang['profile']; ?></a></li>
                <li>
                    <div class="language-switcher">
                        <div class="language-current" onclick="toggleLanguageDropdown()">
                            <?php 
                            $currentLang = getCurrentLanguage();
                            $languages = getAvailableLanguages();
                            echo $languages[$currentLang]['flag'] . ' ' . $languages[$currentLang]['name'];
                            ?>
                        </div>
                        <div class="language-dropdown" id="language-dropdown">
                            <?php foreach ($languages as $code => $info): ?>
                                <a href="javascript:void(0)" class="language-option <?php echo $code === $currentLang ? 'active' : ''; ?>" 
                                   onclick="changeLanguage('<?php echo $code; ?>')">
                                    <?php echo $info['flag'] . ' ' . $info['name']; ?>
                                </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </li>
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
                <li><a href="dashboard.php">📊 <?php echo $lang['dashboard']; ?></a></li>
                <li><a href="services.php">🔧 <?php echo $lang['my_services']; ?></a></li>
                <li><a href="my_bookings.php">📅 <?php echo $lang['bookings']; ?></a></li>
                <li><a href="profile.php">👤 <?php echo $lang['profile']; ?></a></li>
                <li><a href="../auth/logout.php">🚪 <?php echo $lang['logout']; ?></a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title"><?php echo $lang['dashboard']; ?></h1>
                <p class="page-subtitle">Welcome back! Here's your activity overview.</p>
            </div>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $totalBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> <?php echo $lang['bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $pendingBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['pending']; ?> <?php echo $lang['bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $completedBookings; ?></div>
                    <div class="stat-label"><?php echo $lang['completed']; ?> <?php echo $lang['bookings']; ?></div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($totalSpent, 2); ?></div>
                    <div class="stat-label"><?php echo $lang['total']; ?> Spent</div>
                </div>
            </div>

            <!-- Recent Bookings -->
            <div class="card">
                <div class="card-header">
                    <h3>Recent <?php echo $lang['bookings']; ?></h3>
                    <a href="my_bookings.php" class="btn btn-primary btn-sm">View All</a>
                </div>
                <div class="card-body">
                    <?php if ($recentBookings->num_rows > 0): ?>
                        <div class="booking-list">
                            <?php while ($booking = $recentBookings->fetch_assoc()): ?>
                                <div class="booking-item">
                                    <div class="booking-info">
                                        <h4><?php echo htmlspecialchars($booking['service_title']); ?></h4>
                                        <p><strong><?php echo $lang['provider']; ?>:</strong> <?php echo htmlspecialchars($booking['provider_name']); ?></p>
                                        <p><strong><?php echo $lang['status']; ?>:</strong> 
                                            <span class="badge badge-<?php echo $booking['status']; ?>">
                                                <?php echo ucfirst($booking['status']); ?>
                                            </span>
                                        </p>
                                        <p><strong><?php echo $lang['total']; ?>:</strong> $<?php echo number_format($booking['price'], 2); ?></p>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-secondary">No recent bookings found.</p>
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
                        <div class="services-grid">
                            <?php while ($service = $recommendedServices->fetch_assoc()): ?>
                                <div class="service-card">
                                    <div class="service-header">
                                        <h4><?php echo htmlspecialchars($service['title']); ?></h4>
                                        <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                                    </div>
                                    <div class="service-body">
                                        <p><?php echo htmlspecialchars(substr($service['description'], 0, 100)); ?>...</p>
                                        <div class="service-meta">
                                            <span class="provider-name"><?php echo htmlspecialchars($service['provider_name']); ?></span>
                                            <span class="service-price">$<?php echo number_format($service['price'], 2); ?></span>
                                        </div>
                                    </div>
                                    <div class="service-footer">
                                        <a href="book_service_simple.php?id=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm">Book Now</a>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    <?php else: ?>
                        <p class="text-center text-secondary">No recommended services available.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <!-- Language JavaScript -->
    <script>
        // Make language available globally
        window.lang = <?php echo json_encode($lang); ?>;
        
        function toggleLanguageDropdown() {
            const dropdown = document.getElementById('language-dropdown');
            dropdown.classList.toggle('show');
        }
        
        function changeLanguage(language) {
            window.location.href = '../notifications/language_helper.php?lang=' + encodeURIComponent(language);
        }
        
        // Close dropdown when clicking outside
        document.addEventListener('click', function(e) {
            const dropdown = document.getElementById('language-dropdown');
            const switcher = document.querySelector('.language-switcher');
            
            if (dropdown && !switcher.contains(e.target)) {
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
            if (!nav.contains(e.target) && !toggle.contains(e.target)) {
                nav.classList.remove('active');
            }
        });
    </script>

    <!-- Notification System -->
    <script src="../assets/js/notifications.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
