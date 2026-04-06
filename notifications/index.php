<?php
require_once 'config/db.php';

// Get featured services
$featuredServices = $conn->query("
    SELECT s.*, u.name as provider_name, AVG(r.rating) as average_rating
    FROM services s
    JOIN users u ON s.provider_id = u.id
    LEFT JOIN reviews r ON u.id = r.provider_id
    WHERE s.status = 'active'
    GROUP BY s.id
    ORDER BY RAND()
    LIMIT 6
");

// Get service categories and counts
$categories = $conn->query("
    SELECT category, COUNT(*) as count
    FROM services
    WHERE status = 'active'
    GROUP BY category
    ORDER BY count DESC
");

// Get statistics
$totalServices = $conn->query("SELECT COUNT(*) as count FROM services WHERE status = 'active'")->fetch_assoc()['count'];
$totalProviders = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'provider'")->fetch_assoc()['count'];
$totalBookings = $conn->query("SELECT COUNT(*) as count FROM bookings")->fetch_assoc()['count'];
$totalUsers = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'user'")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Service Finder - Find Local Services</title>
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
                <li><a href="auth/login.php">Login</a></li>
                <li><a href="auth/register.php">Register</a></li>
            </ul>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1 class="hero-title">Find Trusted Local Services</h1>
                <p class="hero-subtitle">Connect with verified service providers in your area. From plumbing to tutoring, we've got you covered.</p>
                
                <!-- Quick Search -->
                <div class="hero-search">
                    <form method="GET" action="user/services.php" class="search-form">
                        <input type="text" name="search" placeholder="Search for services, providers, or categories..." class="search-input">
                        <button type="submit" class="btn btn-primary btn-lg">Search Services</button>
                    </form>
                </div>
                
                <!-- Quick Links -->
                <div class="hero-links">
                    <a href="auth/register.php" class="btn btn-secondary btn-lg">Join as Customer</a>
                    <a href="auth/register.php" class="btn btn-outline btn-lg">Become a Provider</a>
                </div>
            </div>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats-section">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalServices; ?>+</div>
                    <div class="stat-label">Active Services</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $totalProviders; ?>+</div>
                    <div class="stat-label">Verified Providers</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $totalBookings; ?>+</div>
                    <div class="stat-label">Services Booked</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $totalUsers; ?>+</div>
                    <div class="stat-label">Happy Customers</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="categories-section">
        <div class="container">
            <div class="section-header">
                <h2>Browse by Category</h2>
                <p>Find services organized by professional categories</p>
            </div>
            
            <div class="categories-grid">
                <?php while ($category = $categories->fetch_assoc()): ?>
                    <a href="user/services.php?category=<?php echo $category['category']; ?>" class="category-card">
                        <div class="category-icon">
                            <?php
                            $icons = [
                                'plumber' => '🔧',
                                'tutor' => '📚',
                                'electrician' => '⚡',
                                'carpenter' => '🔨',
                                'cleaner' => '🧹',
                                'painter' => '🎨',
                                'mechanic' => '🚗',
                                'other' => '📦'
                            ];
                            echo $icons[$category['category']] ?? '🔧';
                            ?>
                        </div>
                        <h3><?php echo ucfirst($category['category']); ?></h3>
                        <p><?php echo $category['count']; ?> services available</p>
                    </a>
                <?php endwhile; ?>
            </div>
        </div>
    </section>

    <!-- Featured Services Section -->
    <section class="featured-section">
        <div class="container">
            <div class="section-header">
                <h2>Featured Services</h2>
                <p>Top-rated services from our trusted providers</p>
                <a href="user/services.php" class="btn btn-primary">View All Services</a>
            </div>
            
            <div class="grid grid-cols-3 gap-6">
                <?php if ($featuredServices->num_rows > 0): ?>
                    <?php while ($service = $featuredServices->fetch_assoc()): ?>
                        <div class="service-card">
                            <div class="service-card-header">
                                <div class="service-category"><?php echo htmlspecialchars($service['category']); ?></div>
                                <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                            </div>
                            <div class="service-card-body">
                                <p><?php echo htmlspecialchars(substr($service['description'], 0, 120)) . '...'; ?></p>
                                
                                <div class="service-price">$<?php echo number_format($service['price'], 2); ?></div>
                                
                                <?php if ($service['average_rating']): ?>
                                    <div class="flex items-center gap-1">
                                        <span class="star">★</span>
                                        <span><?php echo number_format($service['average_rating'], 1); ?></span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="service-provider">
                                    <div class="provider-avatar">
                                        <?php echo strtoupper(substr($service['provider_name'], 0, 1)); ?>
                                    </div>
                                    <div>
                                        <div class="font-medium"><?php echo htmlspecialchars($service['provider_name']); ?></div>
                                        <div class="text-sm text-secondary">Professional Provider</div>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer">
                                <a href="auth/login.php" class="btn btn-primary btn-full">Login to Book</a>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="col-span-3 text-center">
                        <p class="text-secondary">No featured services available at the moment.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- How It Works Section -->
    <section class="how-it-works">
        <div class="container">
            <div class="section-header">
                <h2>How It Works</h2>
                <p>Get started with Smart Service Finder in 4 simple steps</p>
            </div>
            
            <div class="steps-grid">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h3>Sign Up</h3>
                    <p>Create your account as a customer or provider</p>
                </div>
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h3>Browse Services</h3>
                    <p>Explore available services by category or search</p>
                </div>
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h3>Book Service</h3>
                    <p>Select your preferred service and schedule booking</p>
                </div>
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h3>Get Service Done</h3>
                    <p>Provider completes the service and you leave a review</p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <div class="container">
            <div class="cta-content">
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of satisfied customers and trusted service providers</p>
                <div class="cta-buttons">
                    <a href="auth/register.php" class="btn btn-primary btn-lg">Join Now</a>
                    <a href="user/services.php" class="btn btn-outline btn-lg">Browse Services</a>
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
                    <p>Your trusted platform for finding and booking local services. Connect with verified providers and get quality service.</p>
                </div>
                
                <div class="footer-section">
                    <h4>Quick Links</h4>
                    <ul>
                        <li><a href="index.php">Home</a></li>
                        <li><a href="user/services.php">Browse Services</a></li>
                        <li><a href="auth/login.php">Login</a></li>
                        <li><a href="auth/register.php">Register</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>For Providers</h4>
                    <ul>
                        <li><a href="auth/register.php">Become a Provider</a></li>
                        <li><a href="#">Provider Guidelines</a></li>
                        <li><a href="#">Success Stories</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>Support</h4>
                    <ul>
                        <li><a href="#">Help Center</a></li>
                        <li><a href="#">Contact Us</a></li>
                        <li><a href="#">Terms of Service</a></li>
                        <li><a href="#">Privacy Policy</a></li>
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
            background: linear-gradient(135deg, var(--primary-color), var(--primary-light));
            color: white;
            padding: 6rem 0;
            text-align: center;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .hero-search {
            max-width: 600px;
            margin: 0 auto 2rem;
        }
        
        .search-form {
            display: flex;
            gap: 1rem;
            background: white;
            padding: 0.5rem;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
        }
        
        .search-input {
            flex: 1;
            border: none;
            padding: 1rem;
            font-size: 1rem;
            border-radius: var(--radius);
        }
        
        .hero-links {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .btn-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }
        
        .btn-outline:hover {
            background: white;
            color: var(--primary-color);
        }
        
        /* Sections */
        .stats-section {
            padding: 4rem 0;
            background: var(--bg-secondary);
        }
        
        .categories-section,
        .featured-section,
        .how-it-works {
            padding: 4rem 0;
        }
        
        .section-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .section-header h2 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
        }
        
        .section-header p {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
        }
        
        /* Categories */
        .categories-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }
        
        .category-card {
            display: block;
            text-align: center;
            padding: 2rem 1rem;
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
            text-decoration: none;
            color: var(--text-primary);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
        }
        
        .category-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
            border-color: var(--primary-color);
        }
        
        .category-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }
        
        .category-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .category-card p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin: 0;
        }
        
        /* How It Works */
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .step-card {
            text-align: center;
            padding: 2rem;
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-sm);
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 auto 1rem;
        }
        
        .step-card h3 {
            font-size: 1.25rem;
            margin-bottom: 0.5rem;
        }
        
        .step-card p {
            color: var(--text-secondary);
            margin: 0;
        }
        
        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--primary-dark), var(--primary-color));
            color: white;
            padding: 4rem 0;
            text-align: center;
        }
        
        .cta-content h2 {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }
        
        .cta-content p {
            font-size: 1.125rem;
            margin-bottom: 2rem;
            opacity: 0.9;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        /* Footer */
        .footer {
            background: var(--text-primary);
            color: white;
            padding: 3rem 0 1rem;
        }
        
        .footer-content {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        
        .footer-section h3,
        .footer-section h4 {
            margin-bottom: 1rem;
        }
        
        .footer-section ul {
            list-style: none;
            padding: 0;
        }
        
        .footer-section ul li {
            margin-bottom: 0.5rem;
        }
        
        .footer-section a {
            color: var(--text-light);
            text-decoration: none;
        }
        
        .footer-section a:hover {
            color: white;
        }
        
        .footer-bottom {
            text-align: center;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-light);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2rem;
            }
            
            .hero-subtitle {
                font-size: 1rem;
            }
            
            .search-form {
                flex-direction: column;
            }
            
            .section-header h2 {
                font-size: 2rem;
            }
            
            .cta-content h2 {
                font-size: 2rem;
            }
            
            .steps-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>

    <script src="assets/js/main.js"></script>
</body>
</html>
