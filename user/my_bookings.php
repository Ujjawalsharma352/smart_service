<?php
require_once '../config/db.php';
requireRole('user');

$userId = getUserId();

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_review') {
    $bookingId = (int)$_POST['booking_id'];
    $rating = (int)$_POST['rating'];
    $comment = clean_input($_POST['comment']);
    
    // Verify booking belongs to this user and is completed
    $checkBooking = $conn->query("
        SELECT b.provider_id FROM bookings b 
        WHERE b.id = $bookingId AND b.user_id = $userId AND b.status = 'completed'
    ")->fetch_assoc();
    
    if ($checkBooking) {
        // Check if review already exists
        $existingReview = $conn->query("SELECT id FROM reviews WHERE booking_id = $bookingId")->fetch_assoc();
        
        if (!$existingReview) {
            // Use prepared statement for review insertion
            $stmt = $conn->prepare("INSERT INTO reviews (user_id, provider_id, booking_id, rating, comment) 
                                   VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("iiiis", $userId, $checkBooking['provider_id'], $bookingId, $rating, $comment);
            
            if ($stmt->execute()) {
                $success = "Review submitted successfully!";
            } else {
                $error = "Failed to submit review. Please try again.";
            }
            $stmt->close();
        } else {
            $error = "You have already submitted a review for this booking.";
        }
    }
}

// PAGINATION START - Added for pagination
$limit = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Get total records
$totalQuery = $conn->query("SELECT COUNT(*) as total FROM bookings b WHERE b.user_id = $userId");
$totalRecords = $totalQuery->fetch_assoc()['total'];
$totalPages = ceil($totalRecords / $limit);

// Get user's bookings with pagination
$bookings = $conn->query("
    SELECT b.*, s.title as service_title, s.category, s.price as service_price, u.name as provider_name, u.email as provider_email, u.phone as provider_phone,
           r.rating as review_rating, r.comment as review_comment
    FROM bookings b
    JOIN services s ON b.service_id = s.id
    JOIN users u ON b.provider_id = u.id
    LEFT JOIN reviews r ON b.id = r.booking_id
    WHERE b.user_id = $userId
    ORDER BY b.created_at DESC
    LIMIT $limit OFFSET $offset
");

// Debug: Log booking query
error_log("User bookings query executed for user ID: $userId");
error_log("Bookings found: " . $bookings->num_rows);

// Filter by status with pagination
$statusFilter = '';
if (isset($_GET['status'])) {
    $statusFilter = clean_input($_GET['status']);
    $bookings = $conn->query("
        SELECT b.*, s.title as service_title, s.category, s.price as service_price, u.name as provider_name, u.email as provider_email, u.phone as provider_phone,
               r.rating as review_rating, r.comment as review_comment
        FROM bookings b
        JOIN services s ON b.service_id = s.id
        JOIN users u ON b.provider_id = u.id
        LEFT JOIN reviews r ON b.id = r.booking_id
        WHERE b.user_id = $userId AND b.status = '$statusFilter'
        ORDER BY b.created_at DESC
        LIMIT $limit OFFSET $offset
    ");
}

// Search functionality with pagination
$search = '';
if (isset($_GET['search'])) {
    $search = clean_input($_GET['search']);
    $whereClause = "WHERE b.user_id = $userId AND (p.name LIKE '%$search%' OR s.title LIKE '%$search%')";
    
    if ($statusFilter) {
        $whereClause .= " AND b.status = '$statusFilter'";
    }
    
    $bookings = $conn->query("
        SELECT b.*, 
               p.name as provider_name, p.email as provider_email, p.phone as provider_phone,
               s.title as service_title, s.price as service_price, s.category,
               r.rating as review_rating, r.comment as review_comment
        FROM bookings b
        JOIN users p ON b.provider_id = p.id
        JOIN services s ON b.service_id = s.id
        LEFT JOIN reviews r ON b.id = r.booking_id
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
    WHERE user_id = $userId
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
    <title>My Bookings - Smart Service Finder</title>
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
                <li><a href="services.php">Services</a></li>
                <li><a href="my_bookings.php" class="active">My Bookings</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="services.php">🔧 Browse Services</a></li>
                <li><a href="my_bookings.php" class="active">📅 My Bookings</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">My Bookings</h1>
                <p class="page-subtitle">Track and manage your service bookings</p>
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
                    <div class="stat-value"><?php echo ($stats['rejected'] ?? 0) + ($stats['cancelled'] ?? 0); ?></div>
                    <div class="stat-label">Cancelled/Rejected</div>
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
                    <a href="my_bookings.php" class="btn btn-secondary">Clear</a>
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
                                    <th>Service</th>
                                    <th>Provider</th>
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
                                                    <div class="font-medium"><?php echo htmlspecialchars($booking['service_title']); ?></div>
                                                    <div class="service-category"><?php echo htmlspecialchars($booking['category']); ?></div>
                                                </div>
                                            </td>
                                            <td>
                                                <div>
                                                    <div class="font-medium"><?php echo htmlspecialchars($booking['provider_name']); ?></div>
                                                    <div class="text-sm text-secondary"><?php echo htmlspecialchars($booking['provider_email'] ?? ''); ?></div>
                                                    <?php if ($booking['provider_phone']): ?>
                                                        <div class="text-sm text-secondary"><?php echo htmlspecialchars($booking['provider_phone']); ?></div>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="service-price">$<?php echo number_format($booking['service_price'] ?? 0, 2); ?></span>
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
                                                    <button onclick="showBookingDetails(<?php echo $booking['id']; ?>)" 
                                                            class="btn btn-secondary btn-sm">View</button>
                                                    
                                                    <?php if ($booking['status'] === 'completed' && !$booking['review_rating']): ?>
                                                        <button onclick="showReviewForm(<?php echo $booking['id']; ?>)" 
                                                                class="btn btn-primary btn-sm">Review</button>
                                                    <?php endif; ?>
                                                    
                                                    <?php if ($booking['review_rating']): ?>
                                                        <button onclick="showReview(<?php echo $booking['id']; ?>)" 
                                                                class="btn btn-success btn-sm">Rated ★</button>
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

            <!-- Quick Actions -->
            <div class="card mt-8">
                <div class="card-header">
                    <h3>Quick Actions</h3>
                </div>
                <div class="card-body">
                    <div class="flex gap-4">
                        <a href="services.php" class="btn btn-primary">Book New Service</a>
                        <a href="dashboard.php" class="btn btn-secondary">Dashboard</a>
                    </div>
                </div>
            </div>
        </main>
    </div>

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

    <!-- Review Modal -->
    <div id="reviewModal" class="modal" style="display: none;">
        <div class="modal-content">
            <div class="modal-header">
                <h3>Rate Service</h3>
                <button onclick="closeReviewModal()" class="btn btn-secondary">&times;</button>
            </div>
            <div class="modal-body">
                <?php if (isset($success)): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($error)): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" id="reviewForm">
                    <input type="hidden" name="action" value="submit_review">
                    <input type="hidden" name="booking_id" id="reviewBookingId">
                    
                    <div class="form-group">
                        <label>Rating *</label>
                        <div class="rating" id="ratingStars">
                            <span class="star" data-rating="1">★</span>
                            <span class="star" data-rating="2">★</span>
                            <span class="star" data-rating="3">★</span>
                            <span class="star" data-rating="4">★</span>
                            <span class="star" data-rating="5">★</span>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="comment">Comment (Optional)</label>
                        <textarea id="comment" name="comment" rows="4" 
                                  placeholder="Share your experience with this service..."></textarea>
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">Submit Review</button>
                        <button type="button" onclick="closeReviewModal()" class="btn btn-secondary">Cancel</button>
                    </div>
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
        
        .rating {
            display: flex;
            gap: 0.5rem;
            font-size: 2rem;
        }
        
        .rating .star {
            cursor: pointer;
            color: var(--border-color);
            transition: color 0.2s ease;
        }
        
        .rating .star:hover,
        .rating .star.active {
            color: #fbbf24;
        }
        
        .text-sm {
            font-size: 0.875rem;
        }
    </style>

    <script>
        function showBookingDetails(bookingId) {
            // Find booking data from the table
            const rows = document.querySelectorAll('.table tbody tr');
            let bookingData = null;
            
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells[0] && cells[0].textContent === '#' + bookingId) {
                    bookingData = {
                        id: bookingId,
                        service: cells[1].innerHTML,
                        provider: cells[2].innerHTML,
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
                            <h4>Service</h4>
                            <div>${bookingData.service}</div>
                        </div>
                        <div>
                            <h4>Provider</h4>
                            <div>${bookingData.provider}</div>
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
        
        function closeDetailsModal() {
            document.getElementById('detailsModal').style.display = 'none';
        }
        
        function showReviewForm(bookingId) {
            document.getElementById('reviewBookingId').value = bookingId;
            document.getElementById('reviewModal').style.display = 'flex';
        }
        
        function closeReviewModal() {
            document.getElementById('reviewModal').style.display = 'none';
            // Reset rating
            document.querySelectorAll('.rating .star').forEach(star => {
                star.classList.remove('active');
            });
            document.getElementById('ratingInput').value = '';
            document.getElementById('comment').value = '';
        }
        
        function showReview(bookingId) {
            showBookingDetails(bookingId);
        }
        
        // Rating system
        document.querySelectorAll('.rating .star').forEach(star => {
            star.addEventListener('click', function() {
                const rating = this.dataset.rating;
                document.getElementById('ratingInput').value = rating;
                
                // Update visual rating
                document.querySelectorAll('.rating .star').forEach((s, index) => {
                    if (index < rating) {
                        s.classList.add('active');
                    } else {
                        s.classList.remove('active');
                    }
                });
            });
            
            star.addEventListener('mouseenter', function() {
                const rating = this.dataset.rating;
                document.querySelectorAll('.rating .star').forEach((s, index) => {
                    if (index < rating) {
                        s.style.color = '#fbbf24';
                    } else {
                        s.style.color = '';
                    }
                });
            });
        });
        
        document.getElementById('ratingStars').addEventListener('mouseleave', function() {
            document.querySelectorAll('.rating .star').forEach(s => {
                s.style.color = '';
            });
        });
        
        // Close modals when clicking outside
        document.getElementById('detailsModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDetailsModal();
            }
        });
        
        document.getElementById('reviewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeReviewModal();
            }
        });
    </script>

    <script src="../assets/js/main.js"></script>
</body>
</html>
