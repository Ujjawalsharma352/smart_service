<?php
// Enable error reporting for debugging (remove in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Smart Service Finder - Homepage
session_start();

// Initialize database connection with error handling
$database = null;
$conn = null;
$dbError = false;

try {
    require_once 'config/db.php';
    $database = new Database();
    $conn = $database->getConnection();
} catch (Exception $e) {
    $dbError = true;
    error_log("Database connection failed: " . $e->getMessage());
}

// Default values if database fails
$featuredServices = [];
$categories = [];
$stats = [
    'total_services' => 0,
    'total_providers' => 0,
    'total_users' => 0,
    'total_bookings' => 0
];

if (!$dbError && $conn) {
    // Get featured services
    try {
        $sql = "SELECT s.*, u.name as provider_name, u.phone, u.address,
                       COALESCE(AVG(r.rating), 0) as avg_rating,
                       COUNT(r.id) as review_count
                FROM services s
                LEFT JOIN users u ON s.provider_id = u.id
                LEFT JOIN reviews r ON s.id = r.service_id
                WHERE s.status = 'approved'
                GROUP BY s.id
                ORDER BY s.created_at DESC
                LIMIT 6";

        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $featuredServices[] = $row;
            }
        }
    } catch (Exception $e) {
        $featuredServices = [];
    }

    // Get service categories
    try {
        $sql = "SELECT DISTINCT category FROM services WHERE status = 'approved' ORDER BY category";
        $result = $conn->query($sql);
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $categories[] = $row['category'];
            }
        }
    } catch (Exception $e) {
        $categories = [];
    }

    // Get statistics
    try {
        // Total services
        $result = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'approved'");
        if ($result) {
            $stats['total_services'] = $result->fetch_assoc()['count'];
        }

        // Total providers
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'");
        if ($result) {
            $stats['total_providers'] = $result->fetch_assoc()['count'];
        }

        // Total users
        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'");
        if ($result) {
            $stats['total_users'] = $result->fetch_assoc()['count'];
        }

        // Total bookings
        $result = $conn->query("SELECT COUNT(*) as count FROM bookings");
        if ($result) {
            $stats['total_bookings'] = $result->fetch_assoc()['count'];
        }
    } catch (Exception $e) {
        // Keep default stats
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Service Finder - Connect with Local Service Providers</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="navbar-container">
            <a href="index.php" class="navbar-brand">
                🔧 Smart Service Finder
            </a>
            <ul class="navbar-nav">
                <li><a href="index.php" class="active">Home</a></li>
                <li><a href="user/services.php">Services</a></li>
                <li><a href="auth/register.php">Join as Provider</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <li><a href="admin/dashboard.php">Dashboard</a></li>
                    <?php elseif ($_SESSION['role'] === 'provider'): ?>
                        <li><a href="provider/dashboard.php">Dashboard</a></li>
                    <?php else: ?>
                        <li><a href="user/dashboard.php">Dashboard</a></li>
                    <?php endif; ?>
                    <li><a href="auth/logout.php">Logout</a></li>
                <?php else: ?>
                    <li><a href="auth/login.php">Login</a></li>
                    <li><a href="auth/register.php">Register</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Find Trusted Local Services</h1>
                <p>Connect with verified service providers in your area. Book appointments, compare prices, and read reviews from real customers.</p>
                <div class="hero-actions">
                    <a href="user/services.php" class="btn btn-primary btn-lg">Browse Services</a>
                    <a href="auth/register.php" class="btn btn-secondary btn-lg">Become a Provider</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_services']); ?></div>
                    <div class="stat-label">Services Available</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_providers']); ?></div>
                    <div class="stat-label">Verified Providers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_users']); ?></div>
                    <div class="stat-label">Happy Customers</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_bookings']); ?></div>
                    <div class="stat-label">Bookings Completed</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Services -->
    <section class="services-section">
        <div class="container">
            <div class="section-header">
                <h2>Featured Services</h2>
                <p>Discover popular services from our trusted providers</p>
            </div>

            <?php if (!empty($featuredServices)): ?>
                <div class="services-grid">
                    <?php foreach ($featuredServices as $service): ?>
                        <div class="service-card">
                            <div class="service-image">
                                <img src="assets/images/service-placeholder.jpg" alt="<?php echo htmlspecialchars($service['title']); ?>" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMzAwIiBoZWlnaHQ9IjIwMCIgdmlld0JveD0iMCAwIDMwMCAyMDAiIGZpbGw9Im5vbmUiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+CjxyZWN0IHdpZHRoPSIzMDAiIGhlaWdodD0iMjAwIiBmaWxsPSIjRjNGNEY2Ii8+Cjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iMC4zZW0iIGZpbGw9IiM5Q0EzQUYiIGZvbnQtc2l6ZT0iMTgiPk5vIEltYWdlPC90ZXh0Pgo8L3N2Zz4=';">
                            </div>
                            <div class="service-content">
                                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                                <p class="service-category"><?php echo htmlspecialchars($service['category']); ?></p>
                                <p class="service-description"><?php echo htmlspecialchars(substr($service['description'], 0, 100)) . '...'; ?></p>
                                <div class="service-meta">
                                    <div class="service-rating">
                                        <?php
                                        $rating = round($service['avg_rating'], 1);
                                        for ($i = 1; $i <= 5; $i++) {
                                            if ($i <= $rating) {
                                                echo '<i class="fas fa-star"></i>';
                                            } elseif ($i - 0.5 <= $rating) {
                                                echo '<i class="fas fa-star-half-alt"></i>';
                                            } else {
                                                echo '<i class="far fa-star"></i>';
                                            }
                                        }
                                        ?>
                                        <span>(<?php echo $service['review_count']; ?>)</span>
                                    </div>
                                    <div class="service-price">$<?php echo number_format($service['price'], 2); ?></div>
                                </div>
                                <div class="service-provider">
                                    <i class="fas fa-user"></i>
                                    <?php echo htmlspecialchars($service['provider_name']); ?>
                                </div>
                                <a href="user/book_service.php?id=<?php echo $service['id']; ?>" class="btn btn-primary btn-sm">Book Now</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="no-services">
                    <p>No services available at the moment. Check back soon!</p>
                </div>
            <?php endif; ?>

            <div class="section-footer">
                <a href="user/services.php" class="btn btn-secondary">View All Services</a>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <?php if (!empty($categories)): ?>
    <section class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2>Service Categories</h2>
                <p>Find services by category</p>
            </div>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                    <a href="user/services.php?category=<?php echo urlencode($category); ?>" class="category-card">
                        <div class="category-icon">
                            <i class="fas fa-tools"></i>
                        </div>
                        <h3><?php echo htmlspecialchars($category); ?></h3>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of satisfied customers and service providers on our platform.</p>
                <div class="cta-actions">
                    <?php if (!isset($_SESSION['user_id'])): ?>
                        <a href="auth/register.php" class="btn btn-primary btn-lg">Sign Up Free</a>
                        <a href="auth/login.php" class="btn btn-outline btn-lg">Login</a>
                    <?php else: ?>
                        <a href="user/services.php" class="btn btn-primary btn-lg">Browse Services</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>Smart Service Finder</h3>
                    <p>Connecting customers with trusted local service providers since 2024.</p>
                </div>
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="user/services.php">Browse Services</a></li>
                        <li><a href="auth/register.php">Become a Provider</a></li>
                        <li><a href="auth/login.php">Login</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            <div class="footer-bottom">
                <p>&copy; 2024 Smart Service Finder. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <style>
        /* Hero Section */
        .hero {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 100px 0 80px;
            text-align: center;
        }

        .hero-content h1 {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .hero-content p {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .hero-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Statistics Section */
        .stats-section {
            padding: 60px 0;
            background: #f8fafc;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .stat-card {
            text-align: center;
            padding: 2rem;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 1.1rem;
            color: #6b7280;
            font-weight: 500;
        }

        /* Services Section */
        .services-section {
            padding: 80px 0;
        }

        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .section-header h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .section-header p {
            font-size: 1.1rem;
            color: #6b7280;
        }

        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 3rem;
        }

        .service-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .service-image {
            height: 200px;
            background: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .service-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .service-content {
            padding: 1.5rem;
        }

        .service-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #1f2937;
        }

        .service-category {
            color: #3b82f6;
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .service-description {
            color: #6b7280;
            margin-bottom: 1rem;
            line-height: 1.5;
        }

        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }

        .service-rating {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .service-rating i {
            color: #fbbf24;
        }

        .service-price {
            font-size: 1.25rem;
            font-weight: 700;
            color: #059669;
        }

        .service-provider {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #6b7280;
            margin-bottom: 1rem;
        }

        .section-footer {
            text-align: center;
        }

        /* Categories Section */
        .categories-section {
            padding: 80px 0;
            background: #f8fafc;
        }

        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .category-card {
            background: white;
            padding: 2rem;
            border-radius: 12px;
            text-align: center;
            text-decoration: none;
            color: inherit;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .category-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .category-icon {
            font-size: 2rem;
            color: #3b82f6;
            margin-bottom: 1rem;
        }

        .category-card h3 {
            font-size: 1.1rem;
            font-weight: 600;
            color: #1f2937;
        }

        /* CTA Section */
        .cta-section {
            padding: 80px 0;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
            text-align: center;
        }

        .cta-content h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .cta-content p {
            font-size: 1.1rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .cta-actions {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }

        /* Footer */
        .footer {
            background: #1f2937;
            color: white;
            padding: 60px 0 30px;
        }

        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .footer-section h3 {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }

        .footer-section h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }

        .footer-section ul {
            list-style: none;
            padding: 0;
        }

        .footer-section ul li {
            margin-bottom: 0.5rem;
        }

        .footer-section ul li a {
            color: #9ca3af;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .footer-section ul li a:hover {
            color: white;
        }

        .footer-bottom {
            border-top: 1px solid #374151;
            padding-top: 2rem;
            text-align: center;
            color: #9ca3af;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .hero-content h1 {
                font-size: 2.5rem;
            }

            .hero-actions {
                flex-direction: column;
                align-items: center;
            }

            .services-grid {
                grid-template-columns: 1fr;
            }

            .categories-grid {
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            }

            .cta-actions {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</body>
</html>