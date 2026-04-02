<?php
require_once '../config/db.php';
requireRole('provider');

$providerId = getUserId();

// Handle service deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_service') {
    $serviceId = (int)$_POST['service_id'];
    
    // Check if service belongs to this provider
    $service = $conn->query("SELECT id FROM services WHERE id = $serviceId AND provider_id = $providerId")->fetch_assoc();
    
    if ($service) {
        // Check if service has any bookings
        $bookings = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE service_id = $serviceId")->fetch_assoc()['count'];
        
        if ($bookings == 0) {
            $conn->query("DELETE FROM services WHERE id = $serviceId AND provider_id = $providerId");
            $success = "Service deleted successfully!";
        } else {
            $error = "Cannot delete service - it has existing bookings.";
        }
    } else {
        $error = "Service not found!";
    }
}

// Handle service status toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_status') {
    $serviceId = (int)$_POST['service_id'];
    $newStatus = $_POST['status'] === 'active' ? 'active' : 'inactive';
    
    $conn->query("UPDATE services SET status = '$newStatus' WHERE id = $serviceId AND provider_id = $providerId");
    $success = "Service status updated!";
}

// PAGINATION START - Added for pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records
$totalQuery = $conn->query("SELECT COUNT(*) as total FROM services WHERE provider_id = $providerId");
$totalRecords = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get provider's services with pagination
$services = $conn->query("
    SELECT s.*, 
           COUNT(b.id) as booking_count,
           AVG(r.rating) as average_rating,
           COUNT(r.id) as review_count
    FROM services s
    LEFT JOIN bookings b ON s.id = b.service_id
    LEFT JOIN reviews r ON s.id = r.service_id
    WHERE s.provider_id = $providerId
    GROUP BY s.id
    ORDER BY s.created_at DESC
    LIMIT $limit OFFSET $offset
");
// PAGINATION END

// Get statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $providerId")->fetch_assoc()['count'],
    'active' => $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $providerId AND status = 'active'")->fetch_assoc()['count'],
    'inactive' => $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $providerId AND status = 'inactive'")->fetch_assoc()['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Services - Smart Service Finder</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .services-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }
        
        .services-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1.5rem;
        }
        
        .service-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            overflow: hidden;
            transition: transform 0.2s ease;
        }
        
        .service-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        .service-header {
            padding: 1rem;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .service-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .service-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .service-category {
            background: #f3f4f6;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            color: #6b7280;
        }
        
        .service-price {
            font-weight: 600;
            color: #059669;
        }
        
        .service-body {
            padding: 1rem;
        }
        
        .service-description {
            color: #6b7280;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        
        .service-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: #6b7280;
        }
        
        .service-footer {
            padding: 1rem;
            border-top: 1px solid #f3f4f6;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .status-badge {
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .status-active {
            background: #d1fae5;
            color: #065f46;
        }
        
        .status-inactive {
            background: #fee2e2;
            color: #991b1b;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }
        
        .btn-sm {
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            text-decoration: none;
            border: none;
            border-radius: 0.25rem;
            cursor: pointer;
        }
        
        .btn-edit {
            background: #3b82f6;
            color: white;
        }
        
        .btn-delete {
            background: #ef4444;
            color: white;
        }
        
        .btn-toggle {
            background: #f59e0b;
            color: white;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #6ee7b7;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        .no-services {
            text-align: center;
            padding: 4rem 2rem;
            color: #6b7280;
        }
        
        @media (max-width: 768px) {
            .stats-row {
                grid-template-columns: repeat(2, 1fr);
            }
            
            .services-grid {
                grid-template-columns: 1fr;
            }
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
            <ul class="navbar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_services.php" class="active">My Services</a></li>
                <li><a href="manage_bookings.php">Bookings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="my_services.php" class="active">🔧 My Services</a></li>
                <li><a href="add_service.php">➕ Add Service</a></li>
                <li><a href="manage_bookings.php">📅 Manage Bookings</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="services-container">
                <!-- Page Header -->
                <div class="page-header">
                    <h1 class="page-title">My Services</h1>
                    <p class="page-subtitle">Manage your service offerings</p>
                </div>

                <!-- Success/Error Messages -->
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-row">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">Total Services</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['active']; ?></div>
                        <div class="stat-label">Active</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['inactive']; ?></div>
                        <div class="stat-label">Inactive</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo number_format($services->num_rows / $stats['total'] * 100, 0); ?>%</div>
                        <div class="stat-label">With Bookings</div>
                    </div>
                </div>

                <!-- Add Service Button -->
                <div style="margin-bottom: 2rem;">
                    <a href="add_service.php" class="btn btn-primary">
                        ➕ Add New Service
                    </a>
                </div>

                <!-- Services Grid -->
                <div class="services-grid">
                    <?php if ($services->num_rows > 0): ?>
                        <?php while ($service = $services->fetch_assoc()): ?>
                            <div class="service-card">
                                <div class="service-header">
                                    <h3 class="service-title"><?php echo htmlspecialchars($service['title']); ?></h3>
                                    <div class="service-meta">
                                        <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                                        <span class="service-price">$<?php echo number_format($service['price'], 2); ?></span>
                                    </div>
                                </div>
                                <div class="service-body">
                                    <p class="service-description">
                                        <?php echo htmlspecialchars(substr($service['description'], 0, 100)) . '...'; ?>
                                    </p>
                                    <div class="service-stats">
                                        <span>📅 <?php echo $service['booking_count']; ?> bookings</span>
                                        <span>⭐ <?php echo $service['average_rating'] ? number_format($service['average_rating'], 1) : 'No'; ?> rating</span>
                                        <span>💬 <?php echo $service['review_count']; ?> reviews</span>
                                    </div>
                                </div>
                                <div class="service-footer">
                                    <span class="status-badge <?php echo $service['status'] === 'active' ? 'status-active' : 'status-inactive'; ?>">
                                        <?php echo ucfirst($service['status']); ?>
                                    </span>
                                    <div class="action-buttons">
                                        <a href="edit_service.php?id=<?php echo $service['id']; ?>" class="btn-sm btn-edit">Edit</a>
                                        <form method="POST" style="display: inline;">
                                            <input type="hidden" name="action" value="toggle_status">
                                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $service['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="btn-sm btn-toggle">
                                                <?php echo $service['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                        <form method="POST" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this service?');">
                                            <input type="hidden" name="action" value="delete_service">
                                            <input type="hidden" name="service_id" value="<?php echo $service['id']; ?>">
                                            <button type="submit" class="btn-sm btn-delete">Delete</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <div class="no-services">
                            <h3>No Services Found</h3>
                            <p>You haven't created any services yet. Start by adding your first service!</p>
                            <a href="add_service.php" class="btn btn-primary" style="margin-top: 1rem;">
                                ➕ Add Your First Service
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- PAGINATION START - Added for pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 20px; text-align: center;">
                    <div class="pagination-links" style="display: flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?>" 
                               class="btn btn-secondary btn-sm">← Previous</a>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?>" 
                                   class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline-secondary'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?>" 
                                   class="btn btn-secondary btn-sm">Next →</a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 10px; color: #666; font-size: 14px;">
                        Showing <?php echo ($page - 1) * $limit + 1; ?> - <?php echo min($page * $limit, $totalRecords); ?> 
                        of <?php echo $totalRecords; ?> services
                    </div>
                </div>
            <?php endif; ?>
            <!-- PAGINATION END -->
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
