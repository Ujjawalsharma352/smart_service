<?php
require_once '../config/db.php';
requireRole('user');

$userId = getUserId();

// Handle search
$search = '';
$category = '';
$services = [];

if (isset($_GET['search'])) {
    $search = clean_input($_GET['search']);
}

if (isset($_GET['category'])) {
    $category = clean_input($_GET['category']);
}

// Build query
$whereClause = "WHERE s.status = 'active'";

if ($search) {
    $searchTerm = $conn->real_escape_string($search);
    $whereClause .= " AND (s.title LIKE '%$searchTerm%' OR s.description LIKE '%$searchTerm%' OR s.category LIKE '%$searchTerm%')";
}

if ($category) {
    $categoryTerm = $conn->real_escape_string($category);
    $whereClause .= " AND s.category = '$categoryTerm'";
}

// Get services
$sql = "SELECT s.*, u.name as provider_name, u.email as provider_email,
           AVG(r.rating) as average_rating, COUNT(r.id) as review_count
        FROM services s
        JOIN users u ON s.provider_id = u.id
        LEFT JOIN reviews r ON u.id = r.provider_id
        $whereClause
        GROUP BY s.id
        ORDER BY s.created_at DESC";

// Simple query without prepared statements
$services = $conn->query($sql);

// Get categories for filter
$categories = $conn->query("SELECT DISTINCT category FROM services WHERE status = 'active' ORDER BY category");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services - Smart Service Finder</title>
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
                <h1 class="page-title">Browse Services</h1>
                <p class="page-subtitle">Find the perfect service for your needs</p>
            </div>

            <!-- Search and Filter -->
            <div class="search-bar">
                <form method="GET" class="flex gap-4 flex-1">
                    <input type="text" id="search-input" name="search" placeholder="Search services..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input flex-1">
                    <select id="category-filter" name="category" class="filter-dropdown">
                        <option value="">All Categories</option>
                        <?php while ($cat = $categories->fetch_assoc()): ?>
                            <option value="<?php echo htmlspecialchars($cat['category']); ?>" 
                                    <?php echo $category === $cat['category'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['category']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
            </div>

            <!-- Loading Indicator -->
            <div id="search-loading" style="display: none; text-align: center; padding: 20px;">
                <div style="color: #666;">🔄 Searching services...</div>
            </div>

            <!-- Results Count -->
            <div style="margin: 10px 0; color: #666;">
                Found <span id="results-count"><?php echo $services->num_rows; ?></span> services
            </div>

            <!-- Services Grid -->
            <div id="services-results" class="services-grid">
                <?php if ($services->num_rows > 0): ?>
                    <?php while ($service = $services->fetch_assoc()): ?>
                        <div class="service-card">
                            <div class="service-content">
                                <div class="service-header">
                                    <h3><?php echo htmlspecialchars($service['title']); ?></h3>
                                    <div class="service-meta">
                                        <span class="badge badge-primary"><?php echo htmlspecialchars($service['category']); ?></span>
                                        <span class="service-price">$<?php echo number_format($service['price'], 2); ?></span>
                                    </div>
                                </div>
                                <p class="service-description">
                                    <?php echo htmlspecialchars(substr($service['description'], 0, 150)) . '...'; ?>
                                </p>
                                <div class="service-footer">
                                    <div class="provider-info">
                                        <div class="provider-avatar">
                                            <?php echo strtoupper(substr($service['provider_name'], 0, 1)); ?>
                                        </div>
                                        <div>
                                            <div class="provider-name"><?php echo htmlspecialchars($service['provider_name']); ?></div>
                                            <div class="provider-rating">
                                                <?php if ($service['average_rating']): ?>
                                                    <span class="star">★</span>
                                                    <span><?php echo number_format($service['average_rating'], 1); ?></span>
                                                <?php else: ?>
                                                    <span class="no-rating">No rating</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="service-actions">
                                        <a href="book_service.php?id=<?php echo $service['id']; ?>" 
                                           class="btn btn-primary btn-sm">Book Service</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="no-results">
                        <h3>No services found</h3>
                        <p>Try adjusting your search criteria or browse all categories.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <style>
        /* Search Bar Fix */
        .search-bar {
            margin-bottom: 2rem;
        }
        
        .search-bar form {
            display: flex;
            gap: 1rem;
            align-items: center;
            width: 100%;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
        }
        
        .search-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .filter-dropdown {
            padding: 0.75rem 1rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
            background: white;
        }
        
        .filter-dropdown:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        /* Services Grid */
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-top: 2rem;
        }
        
        .service-card {
            background: var(--bg-primary);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .service-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }
        
        .service-content {
            padding: 1.5rem;
        }
        
        .service-header h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
        }
        
        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .service-description {
            color: var(--text-secondary);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .service-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .provider-info {
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }
        
        .provider-avatar {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            font-size: 0.875rem;
        }
        
        .provider-name {
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .provider-rating {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .star {
            color: #fbbf24;
        }
        
        .no-rating {
            color: var(--text-secondary);
        }
        
        .no-results {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
        }
        
        .no-results h3 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
            color: var(--text-primary);
        }
        
        @media (max-width: 768px) {
            .services-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .search-bar form {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .search-input,
            .filter-dropdown {
                width: 100%;
            }
        }
    </style>

    <script src="../assets/js/main.js"></script>
    <script>
        // Dynamic search functionality
        let searchTimeout;
        
        function performSearch() {
            const searchInput = document.getElementById('search-input');
            const categorySelect = document.getElementById('category-filter');
            const resultsContainer = document.getElementById('services-results');
            const loadingIndicator = document.getElementById('search-loading');
            
            const searchTerm = searchInput.value.trim();
            const category = categorySelect.value;
            
            // Show loading
            loadingIndicator.style.display = 'block';
            resultsContainer.style.opacity = '0.5';
            
            // Clear previous timeout
            clearTimeout(searchTimeout);
            
            // Set new timeout for debouncing
            searchTimeout = setTimeout(() => {
                const params = new URLSearchParams({
                    search: searchTerm,
                    category: category,
                    ajax: '1'
                });
                
                fetch(`services.php?${params}`)
                    .then(response => response.text())
                    .then(html => {
                        // Create a temporary div to parse the response
                        const tempDiv = document.createElement('div');
                        tempDiv.innerHTML = html;
                        
                        // Extract the services grid
                        const newGrid = tempDiv.querySelector('#services-results');
                        if (newGrid) {
                            resultsContainer.innerHTML = newGrid.innerHTML;
                        }
                        
                        loadingIndicator.style.display = 'none';
                        resultsContainer.style.opacity = '1';
                        
                        // Update result count
                        updateResultCount();
                    })
                    .catch(error => {
                        console.error('Search error:', error);
                        loadingIndicator.style.display = 'none';
                        resultsContainer.style.opacity = '1';
                    });
            }, 300); // 300ms debounce
        }
        
        function updateResultCount() {
            const serviceCards = document.querySelectorAll('.service-card');
            const countElement = document.getElementById('results-count');
            if (countElement) {
                countElement.textContent = serviceCards.length;
            }
        }
        
        function initializeSearch() {
            const searchInput = document.getElementById('search-input');
            const categorySelect = document.getElementById('category-filter');
            
            if (searchInput) {
                searchInput.addEventListener('input', performSearch);
            }
            
            if (categorySelect) {
                categorySelect.addEventListener('change', performSearch);
            }
            
            // Initialize result count
            updateResultCount();
        }
        
        // Auto-refresh for new services
        function checkForNewServices() {
            const lastCheck = localStorage.getItem('lastServiceCheck') || 0;
            const now = Date.now();
            
            // Only check every 5 minutes
            if (now - lastCheck > 300000) {
                fetch('../api/services/latest.php')
                    .then(response => response.json())
                    .then(data => {
                        if (data.success && data.new_services > 0) {
                            showNotification(`${data.new_services} new services available! Refresh to see them.`, 'info');
                        }
                        localStorage.setItem('lastServiceCheck', now.toString());
                    })
                    .catch(error => console.log('Service check error:', error));
            }
        }
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                padding: 15px;
                border-radius: 4px;
                color: white;
                z-index: 1000;
                background: ${type === 'info' ? '#17a2b8' : type === 'success' ? '#28a745' : '#dc3545'};
            `;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            initializeSearch();
            
            // Check for new services every 2 minutes
            setInterval(checkForNewServices, 120000);
            checkForNewServices(); // Initial check
        });
    </script>
</body>
</html>
