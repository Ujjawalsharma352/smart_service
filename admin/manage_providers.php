<?php
require_once '../config/db.php';
requireRole('admin');

// Handle provider actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'toggle_status') {
        $providerId = (int)$_POST['provider_id'];
        $newStatus = $_POST['status'] === 'active' ? 'inactive' : 'active';
        
        // Update provider's services status
        $conn->query("UPDATE services SET status = '$newStatus' WHERE provider_id = $providerId");
        $success = "Provider services updated successfully";
    }
    
    if ($action === 'delete_provider') {
        $providerId = (int)$_POST['provider_id'];
        
        // Delete provider (cascades will handle related records)
        $conn->query("DELETE FROM users WHERE id = $providerId AND role = 'provider'");
        $success = "Provider deleted successfully";
    }
}

// PAGINATION START - Added for pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records
$totalQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'provider'");
$totalRecords = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get all providers with their service counts and pagination
$providers = $conn->query("
    SELECT u.*, 
           COUNT(s.id) as service_count,
           COUNT(b.id) as booking_count,
           AVG(r.rating) as average_rating
    FROM users u
    LEFT JOIN services s ON u.id = s.provider_id
    LEFT JOIN bookings b ON u.id = b.provider_id
    LEFT JOIN reviews r ON u.id = r.provider_id
    WHERE u.role = 'provider'
    GROUP BY u.id
    ORDER BY u.created_at DESC
    LIMIT $limit OFFSET $offset
");

// Search functionality with pagination
$search = '';
if (isset($_GET['search'])) {
    $search = clean_input($_GET['search']);
    
    // Get total records for search
    $searchTotalQuery = $conn->query("
        SELECT COUNT(*) as total FROM users u
        WHERE u.role = 'provider' 
        AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')
    ");
    $totalRecords = $searchTotalQuery->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    $providers = $conn->query("
        SELECT u.*, 
               COUNT(s.id) as service_count,
               COUNT(b.id) as booking_count,
               AVG(r.rating) as average_rating
        FROM users u
        LEFT JOIN services s ON u.id = s.provider_id
        LEFT JOIN bookings b ON u.id = b.provider_id
        LEFT JOIN reviews r ON u.id = r.provider_id
        WHERE u.role = 'provider'
        AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%')
        GROUP BY u.id
        ORDER BY u.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
}
// PAGINATION END
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Providers - Smart Service Finder</title>
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
                <li><a href="manage_users.php">Users</a></li>
                <li><a href="manage_providers.php" class="active">Providers</a></li>
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
                <li><a href="manage_users.php">👥 Users</a></li>
                <li><a href="manage_providers.php" class="active">🔧 Providers</a></li>
                <li><a href="manage_bookings.php">📅 Bookings</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manage Providers</h1>
                <p class="page-subtitle">View and manage all service providers</p>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" class="flex gap-4 flex-1">
                    <input type="text" name="search" placeholder="Search providers by name or email..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input flex-1">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                <?php if ($search): ?>
                    <a href="manage_providers.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>

            <!-- Providers Table -->
            <div class="card">
                <div class="card-header">
                    <h3>All Providers</h3>
                    <span class="badge badge-success"><?php echo $providers->num_rows; ?> providers</span>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Email</th>
                                    <th>Phone</th>
                                    <th>Services</th>
                                    <th>Bookings</th>
                                    <th>Rating</th>
                                    <th>Address</th>
                                    <th>Joined</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($providers->num_rows > 0): ?>
                                    <?php while ($provider = $providers->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $provider['id']; ?></td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div class="provider-avatar">
                                                        <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium"><?php echo htmlspecialchars($provider['name']); ?></div>
                                                        <div class="badge badge-success">Provider</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($provider['email']); ?></td>
                                            <td><?php echo htmlspecialchars($provider['phone'] ?: 'N/A'); ?></td>
                                            <td>
                                                <span class="badge badge-info"><?php echo $provider['service_count']; ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-warning"><?php echo $provider['booking_count']; ?></span>
                                            </td>
                                            <td>
                                                <?php if ($provider['average_rating']): ?>
                                                    <div class="flex items-center gap-1">
                                                        <span class="star">★</span>
                                                        <span><?php echo number_format($provider['average_rating'], 1); ?></span>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-light">No rating</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($provider['address'], 0, 30) . '...'); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($provider['created_at'])); ?></td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <button onclick="viewProviderDetails(<?php echo $provider['id']; ?>)" 
                                                            class="btn btn-primary btn-sm">View</button>
                                                    <button onclick="confirmDelete(<?php echo $provider['id']; ?>, '<?php echo htmlspecialchars($provider['name']); ?>')" 
                                                            class="btn btn-danger btn-sm">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-secondary">
                                            No providers found
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- PAGINATION START - Added for pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="pagination" style="margin-top: 20px; text-align: center;">
                    <div class="pagination-links" style="display: flex; justify-content: center; gap: 5px; flex-wrap: wrap;">
                        <!-- Previous Button -->
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="btn btn-secondary btn-sm">Previous</a>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline-secondary'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn btn-secondary btn-sm">Next</a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 10px; color: #666; font-size: 14px;">
                        Showing <?php echo ($page - 1) * $limit + 1; ?> - <?php echo min($page * $limit, $totalRecords); ?> 
                        of <?php echo $totalRecords; ?> providers
                    </div>
                </div>
            <?php endif; ?>
            <!-- PAGINATION END -->
        </main>
    </div>

    <!-- Provider Details Modal -->
    <div id="detailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Provider Details</h3>
                <button onclick="closeDetailsModal()" class="btn btn-secondary">&times;</button>
            </div>
            <div class="modal-body" id="providerDetails">
                <!-- Content will be loaded via AJAX -->
            </div>
            <div class="modal-footer">
                <button onclick="closeDetailsModal()" class="btn btn-secondary">Close</button>
            </div>
        </div>
    </div>

    <!-- Delete Provider Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete Provider</h3>
                <button onclick="closeDeleteModal()" class="btn btn-secondary">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteProviderName"></strong>?</p>
                <p class="text-warning">This action cannot be undone and will delete all associated services and bookings.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_provider">
                    <input type="hidden" name="provider_id" id="deleteProviderId">
                    <button type="button" onclick="closeDeleteModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Provider</button>
                </form>
            </div>
        </div>
    </div>

    <style>
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }
        
        .modal-content {
            background: white;
            border-radius: var(--radius-lg);
            max-width: 600px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-body {
            padding: 1.5rem;
        }
        
        .modal-footer {
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }
    </style>

    <script>
        function viewProviderDetails(providerId) {
            // Load provider details via AJAX
            fetch(`get_provider_details.php?id=${providerId}`)
                .then(response => response.text())
                .then(html => {
                    document.getElementById('providerDetails').innerHTML = html;
                    document.getElementById('detailsModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error loading provider details:', error);
                    alert('Error loading provider details');
                });
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        function confirmDelete(providerId, providerName) {
            document.getElementById('deleteProviderId').value = providerId;
            document.getElementById('deleteProviderName').textContent = providerName;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
        // Close modals when clicking outside
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });
        
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
