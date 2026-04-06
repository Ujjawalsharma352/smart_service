<?php
require_once '../config/db.php';
requireRole('admin');

// Handle user actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'delete_user') {
        $userId = (int)$_POST['user_id'];
        
        // Don't allow deleting admin users
        $checkAdmin = $conn->query("SELECT role FROM users WHERE id = $userId")->fetch_assoc();
        if ($checkAdmin && $checkAdmin['role'] === 'admin') {
            $error = "Cannot delete admin users";
        } else {
            // Delete user (cascades will handle related records)
            $conn->query("DELETE FROM users WHERE id = $userId");
            $success = "User deleted successfully";
        }
    }
    
    if ($action === 'toggle_status') {
        $userId = (int)$_POST['user_id'];
        $newStatus = $_POST['status'] === 'active' ? 'inactive' : 'active';
        
        // For users, we don't have a status field, so we'll simulate by updating a note
        $conn->query("UPDATE users SET address = CONCAT(COALESCE(address, ''), ' [Status: $newStatus]') WHERE id = $userId");
        $success = "User status updated";
    }
}

// PAGINATION START - Added for pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records
$totalQuery = $conn->query("SELECT COUNT(*) as total FROM users WHERE role != 'admin'");
$totalRecords = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get all users (excluding admin) with pagination
$users = $conn->query("
    SELECT id, name, email, phone, address, created_at, role
    FROM users 
    WHERE role != 'admin'
    ORDER BY created_at DESC
    LIMIT $limit OFFSET $offset
");

// Search functionality with pagination
$search = '';
if (isset($_GET['search'])) {
    $search = clean_input($_GET['search']);
    
    // Get total records for search
    $searchTotalQuery = $conn->query("
        SELECT COUNT(*) as total FROM users 
        WHERE role != 'admin' 
        AND (name LIKE '%$search%' OR email LIKE '%$search%')
    ");
    $totalRecords = $searchTotalQuery->fetch_assoc()['total'];
    $totalPages = ceil($totalRecords / $limit);
    
    $users = $conn->query("
        SELECT id, name, email, phone, address, created_at, role
        FROM users 
        WHERE role != 'admin' 
        AND (name LIKE '%$search%' OR email LIKE '%$search%')
        ORDER BY created_at DESC
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
    <title>Manage Users - Smart Service Finder</title>
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
                <li><a href="manage_users.php" class="active">Users</a></li>
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
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="manage_users.php" class="active">👥 Users</a></li>
                <li><a href="manage_providers.php">🔧 Providers</a></li>
                <li><a href="manage_bookings.php">📅 Bookings</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manage Users</h1>
                <p class="page-subtitle">View and manage all registered users</p>
            </div>

            <!-- Search Bar -->
            <div class="search-bar">
                <form method="GET" class="flex gap-4 flex-1">
                    <input type="text" name="search" placeholder="Search users by name or email..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input flex-1">
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                <?php if ($search): ?>
                    <a href="manage_users.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>

            <!-- Users Table -->
            <div class="card">
                <div class="card-header">
                    <h3>All Users</h3>
                    <span class="badge badge-info"><?php echo $users->num_rows; ?> users</span>
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
                                    <th>Role</th>
                                    <th>Address</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($users->num_rows > 0): ?>
                                    <?php while ($user = $users->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $user['id']; ?></td>
                                            <td>
                                                <div class="flex items-center gap-2">
                                                    <div class="provider-avatar">
                                                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <div class="font-medium"><?php echo htmlspecialchars($user['name']); ?></div>
                                                        <div class="badge badge-<?php echo $user['role'] === 'provider' ? 'success' : 'primary'; ?>">
                                                            <?php echo ucfirst($user['role']); ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td><?php echo htmlspecialchars($user['phone'] ?: 'N/A'); ?></td>
                                            <td>
                                                <?php 
                                                if ($user['role'] === 'provider') {
                                                    $serviceCount = $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = " . $user['id'])->fetch_assoc()['count'];
                                                    echo '<span class="badge badge-info">' . $serviceCount . '</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($user['role'] === 'provider') {
                                                    $bookingCount = $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = " . $user['id'])->fetch_assoc()['count'];
                                                    echo '<span class="badge badge-warning">' . $bookingCount . '</span>';
                                                } else {
                                                    echo '<span class="badge badge-secondary">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php 
                                                if ($user['role'] === 'provider') {
                                                    $avgRating = $conn->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE provider_id = " . $user['id'])->fetch_assoc()['avg_rating'];
                                                    if ($avgRating) {
                                                        echo '<div class="flex items-center gap-1"><span class="star">★</span><span>' . number_format($avgRating, 1) . '</span></div>';
                                                    } else {
                                                        echo '<span class="text-light">No rating</span>';
                                                    }
                                                } else {
                                                    echo '<span class="badge badge-secondary">-</span>';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars(substr($user['address'], 0, 30) . '...'); ?></td>
                                            <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="flex gap-2">
                                                    <button onclick="confirmDelete(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['name']); ?>')" 
                                                            class="btn btn-danger btn-sm">Delete</button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-secondary">
                                            No users found
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
                                   class="btn btn-secondary btn-sm">Next →</a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 10px; color: #666; font-size: 14px;">
                        Showing <?php echo ($page - 1) * $limit + 1; ?> - <?php echo min($page * $limit, $totalRecords); ?> 
                        of <?php echo $totalRecords; ?> users
                    </div>
                </div>
            <?php endif; ?>
            <!-- PAGINATION END -->
        </main>
    </div>

    <!-- Delete User Modal -->
    <div id="deleteModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Delete User</h3>
                <button onclick="closeModal()" class="btn btn-secondary">&times;</button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete <strong id="deleteUserName"></strong>?</p>
                <p class="text-warning">This action cannot be undone and will delete all associated data.</p>
            </div>
            <div class="modal-footer">
                <form method="POST" id="deleteForm">
                    <input type="hidden" name="action" value="delete_user">
                    <input type="hidden" name="user_id" id="deleteUserId">
                    <button type="button" onclick="closeModal()" class="btn btn-secondary">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete User</button>
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
            max-width: 500px;
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
        function confirmDelete(userId, userName) {
            document.getElementById('deleteUserId').value = userId;
            document.getElementById('deleteUserName').textContent = userName;
            document.getElementById('deleteModal').style.display = 'flex';
        }
        
        function closeModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }
        
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
        
        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
