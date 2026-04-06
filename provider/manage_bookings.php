<?php
require_once '../config/db.php';
require_once '../notifications/notification_functions.php';
require_once '../notifications/language_helper.php';
requireRole('provider');

$providerId = getUserId();

// Handle booking actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    
    if ($action === 'update_status') {
        $bookingId = (int)$_POST['booking_id'];
        $newStatus = clean_input($_POST['status']);
        
        // Verify booking belongs to this provider
        $checkBooking = $conn->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch_assoc();
        if ($checkBooking && $checkBooking['provider_id'] == $providerId) {
            $conn->query("UPDATE bookings SET status = '$newStatus' WHERE id = $bookingId");
            $success = "Booking status updated successfully";
            
            // Send booking status notifications
            createStatusUpdateNotifications($bookingId, $newStatus, $checkBooking['user_id'], $checkBooking['provider_id']);
        }
    }
}

// PAGINATION START - Added for pagination
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records
$totalQuery = $conn->query("SELECT COUNT(*) as total FROM bookings b WHERE b.provider_id = $providerId");
$totalRecords = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get provider's bookings with pagination
$bookings = $conn->query("
    SELECT b.*, 
           u.name as user_name, u.email as user_email, u.phone as user_phone,
           s.title as service_title, s.price as service_price, s.category
    FROM bookings b
    JOIN users u ON b.user_id = u.id
    JOIN services s ON b.service_id = s.id
    WHERE b.provider_id = $providerId
    ORDER BY b.created_at DESC
    LIMIT $limit OFFSET $offset
");

// Filter by status with pagination
$statusFilter = '';
if (isset($_GET['status'])) {
    $statusFilter = clean_input($_GET['status']);
    $bookings = $conn->query("
        SELECT b.*, 
               u.name as user_name, u.email as user_email, u.phone as user_phone,
               s.title as service_title, s.price as service_price, s.category
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN services s ON b.service_id = s.id
        WHERE b.provider_id = $providerId AND b.status = '$statusFilter'
        ORDER BY b.created_at DESC
    ");
}

// Search functionality
$search = '';
if (isset($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $whereClause = "WHERE b.provider_id = $providerId AND (u.name LIKE '%$search%' OR u.email LIKE '%$search%' OR s.title LIKE '%$search%')";
    
    if ($statusFilter) {
        $whereClause .= " AND b.status = '$statusFilter'";
    }
    
    $bookings = $conn->query("
        SELECT b.*, 
               u.name as user_name, u.email as user_email, u.phone as user_phone,
               s.title as service_title, s.price as service_price, s.category
        FROM bookings b
        JOIN users u ON b.user_id = u.id
        JOIN services s ON b.service_id = s.id
        $whereClause
        ORDER BY b.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
}
// PAGINATION END

// Get booking statistics
$bookingStats = $conn->query("
    SELECT status, COUNT(*) as count
    FROM bookings
    WHERE provider_id = $providerId
    GROUP BY status
");
$stats = [];
while ($row = $bookingStats->fetch_assoc()) {
    $stats[$row['status']] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Bookings - Smart Service Finder</title>
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
                <li><a href="add_service.php">Add Service</a></li>
                <li><a href="manage_bookings.php" class="active">Bookings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="add_service.php">➕ Add Service</a></li>
                <li><a href="manage_bookings.php" class="active">📅 Manage Bookings</a></li>
                <li><a href="my_services.php">🔧 My Services</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Manage Bookings</h1>
                <p class="page-subtitle">View and manage your service bookings</p>
            </div>

            <!-- Statistics Cards -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $stats['pending'] ?? 0; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
                <div class="stat-card success">
                    <div class="stat-value"><?php echo $stats['accepted'] ?? 0; ?></div>
                    <div class="stat-label">Accepted</div>
                </div>
                <div class="stat-card warning">
                    <div class="stat-value"><?php echo $stats['completed'] ?? 0; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
                <div class="stat-card danger">
                    <div class="stat-value"><?php echo $stats['rejected'] ?? 0; ?></div>
                    <div class="stat-label">Rejected</div>
                </div>
            </div>

            <!-- Search and Filter Bar -->
            <div class="search-bar">
                <form method="GET" class="flex gap-4 flex-1">
                    <input type="text" name="search" placeholder="Search bookings..." 
                           value="<?php echo htmlspecialchars($search); ?>" class="search-input flex-1">
                    <select name="status" class="filter-dropdown">
                        <option value="">All Status</option>
                        <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                        <option value="accepted" <?php echo $statusFilter === 'accepted' ? 'selected' : ''; ?>>Accepted</option>
                        <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                        <option value="rejected" <?php echo $statusFilter === 'rejected' ? 'selected' : ''; ?>>Rejected</option>
                        <option value="cancelled" <?php echo $statusFilter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                </form>
                <?php if ($search || $statusFilter): ?>
                    <a href="manage_bookings.php" class="btn btn-secondary">Clear</a>
                <?php endif; ?>
            </div>

            <!-- Bookings Table -->
            <div class="card">
                <div class="card-header">
                    <h3>Your Bookings</h3>
                    <span class="badge badge-info"><?php echo $bookings->num_rows; ?> bookings</span>
                </div>
                <div class="card-body">
                    <div class="table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Customer</th>
                                    <th>Service</th>
                                    <th>Price</th>
                                    <th>Date</th>
                                    <th>Address</th>
                                    <th>Status</th>
                                    <th>Booked</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($bookings->num_rows > 0): ?>
                                    <?php while ($booking = $bookings->fetch_assoc()): ?>
                                        <tr>
                                            <td>#<?php echo $booking['id']; ?></td>
                                            <td>
                                                <div>
                                                    <div class="font-medium"><?php echo htmlspecialchars($booking['user_name']); ?></div>
                                                    <div class="text-sm text-secondary"><?php echo htmlspecialchars($booking['user_email']); ?></div>
                                                    <?php if ($booking['user_phone']): ?>
                                                        <div class="text-sm text-secondary"><?php echo htmlspecialchars($booking['user_phone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-medium"><?php echo htmlspecialchars($booking['service_title']); ?></div>
                                                    <div class="service-category"><?php echo htmlspecialchars($booking['category']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="service-price">$<?php echo number_format($booking['service_price'], 2); ?></span>
                                            </td>
                                            <td>
                                                <div>
                                                    <div><?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></div>
                                                    <?php if ($booking['time_slot']): ?>
                                                        <div class="text-sm text-secondary"><?php echo htmlspecialchars($booking['time_slot']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-sm" style="max-width: 200px;">
                                                    <?php echo htmlspecialchars(substr($booking['address'], 0, 50)) . '...'; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge badge-<?php echo $booking['status']; ?>">
                                                    <?php echo ucfirst($booking['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($booking['created_at'])); ?></td>
                                            <td>
                                                <div class="flex gap-2 flex-col">
                                                    <?php if ($booking['status'] === 'pending'): ?>
                                                        <button onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'accepted')" 
                                                                class="btn btn-success btn-sm">Accept</button>
                                                        <button onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'rejected')" 
                                                                class="btn btn-danger btn-sm">Reject</button>
                                                    <?php elseif ($booking['status'] === 'accepted'): ?>
                                                        <button onclick="updateBookingStatus(<?php echo $booking['id']; ?>, 'completed')" 
                                                                class="btn btn-primary btn-sm">Complete</button>
                                                        <button onclick="showCustomerDetails(<?php echo $booking['id']; ?>)" 
                                                                class="btn btn-secondary btn-sm">Contact</button>
                                                    <?php else: ?>
                                                        <button onclick="showBookingDetails(<?php echo $booking['id']; ?>)" 
                                                                class="btn btn-secondary btn-sm">View</button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="9" class="text-center text-secondary">
                                            No bookings found
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
                            <a href="?page=<?php echo $page - 1; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                               class="btn btn-secondary btn-sm">← Previous</a>
                        <?php endif; ?>
                        
                        <!-- Page Numbers -->
                        <?php 
                        $startPage = max(1, $page - 2);
                        $endPage = min($totalPages, $page + 2);
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                        ?>
                            <a href="?page=<?php echo $i; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-outline-secondary'; ?> btn-sm">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next Button -->
                        <?php if ($page < $totalPages): ?>
                            <a href="?page=<?php echo $page + 1; ?><?php echo $statusFilter ? '&status=' . urlencode($statusFilter) : ''; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>" 
                                   class="btn btn-secondary btn-sm">Next →</a>
                        <?php endif; ?>
                    </div>
                    
                    <div style="margin-top: 10px; color: #666; font-size: 14px;">
                        Showing <?php echo ($page - 1) * $limit + 1; ?> - <?php echo min($page * $limit, $totalRecords); ?> 
                        of <?php echo $totalRecords; ?> bookings
                    </div>
                </div>
            <?php endif; ?>
            <!-- PAGINATION END -->
        </main>
    </div>

    <!-- Update Status Form (Hidden) -->
    <form method="POST" id="statusForm" style="display: none;">
        <input type="hidden" name="action" value="update_status">
        <input type="hidden" name="booking_id" id="statusBookingId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <!-- Booking Details Modal -->
    <div id="detailsModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Booking Details</h3>
                <button onclick="closeDetailsModal()" class="btn btn-secondary">&times;</button>
            </div>
            <div class="modal-body" id="bookingDetails">
                <!-- Content will be loaded via JavaScript -->
            </div>
            <div class="modal-footer">
                <button onclick="closeDetailsModal()" class="btn btn-secondary">Close</button>
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
        
        .text-sm {
            font-size: 0.875rem;
        }
    </style>

    <script>
        function updateBookingStatus(bookingId, newStatus) {
            if (confirm('Are you sure you want to ' + newStatus + ' this booking?')) {
                document.getElementById('statusBookingId').value = bookingId;
                document.getElementById('statusValue').value = newStatus;
                document.getElementById('statusForm').submit();
            }
        }
        
        function showBookingDetails(bookingId) {
            // Find booking data from the table
            const rows = document.querySelectorAll('.table tbody tr');
            let bookingData = null;
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells[0] && cells[0].textContent === '#' + bookingId) {
                    bookingData = {
                        id: bookingId,
                        customer: cells[1].innerHTML,
                        service: cells[2].innerHTML,
                        price: cells[3].innerHTML,
                        date: cells[4].innerHTML,
                        address: cells[5].textContent,
                        status: cells[6].innerHTML,
                        booked: cells[7].textContent
                    };
                }
            });
            
            if (bookingData) {
                const detailsHtml = `
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4>Booking ID</h4>
                            <p>#${bookingData.id}</p>
                        </div>
                        <div>
                            <h4>Status</h4>
                            <p>${bookingData.status}</p>
                        </div>
                        <div>
                            <h4>Customer</h4>
                            <div>${bookingData.customer}</div>
                        </div>
                        <div>
                            <h4>Service</h4>
                            <div>${bookingData.service}</div>
                        </div>
                        <div>
                            <h4>Price</h4>
                            <p>${bookingData.price}</p>
                        </div>
                        <div>
                            <h4>Date</h4>
                            <div>${bookingData.date}</div>
                        </div>
                        <div class="col-span-2">
                            <h4>Service Address</h4>
                            <p>${bookingData.address}</p>
                        </div>
                        <div class="col-span-2">
                            <h4>Booked On</h4>
                            <p>${bookingData.booked}</p>
                        </div>
                    </div>
                `;
                
                document.getElementById('bookingDetails').innerHTML = detailsHtml;
                document.getElementById('detailsModal').style.display = 'flex';
            }
        }
        
        function showCustomerDetails(bookingId) {
            showBookingDetails(bookingId);
        }
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        // Close modal when clicking outside
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
