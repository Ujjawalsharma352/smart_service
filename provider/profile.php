<?php
require_once '../config/db.php';
requireRole('provider');

$providerId = getUserId();

// Get provider information
$provider = $conn->query("SELECT * FROM users WHERE id = $providerId")->fetch_assoc();

// Get provider's service statistics
$stats = [
    'total_services' => $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $providerId")->fetch_assoc()['count'],
    'active_services' => $conn->query("SELECT COUNT(*) as count FROM services WHERE provider_id = $providerId AND status = 'active'")->fetch_assoc()['count'],
    'total_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $providerId")->fetch_assoc()['count'],
    'completed_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $providerId AND status = 'completed'")->fetch_assoc()['count'],
    'pending_bookings' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE provider_id = $providerId AND status = 'pending'")->fetch_assoc()['count'],
    'average_rating' => $conn->query("SELECT AVG(rating) as avg_rating FROM reviews WHERE provider_id = $providerId")->fetch_assoc()['avg_rating'],
    'total_reviews' => $conn->query("SELECT COUNT(*) as count FROM reviews WHERE provider_id = $providerId")->fetch_assoc()['count']
];

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    $description = clean_input($_POST['description']);
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = "Name and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email is already taken by another user
        $checkEmail = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $providerId")->fetch_assoc();
        if ($checkEmail) {
            $error = "Email is already taken by another user";
        } else {
            // Update profile
            $sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', address = '$address', description = '$description' WHERE id = $providerId";
            if ($conn->query($sql)) {
                $success = "Profile updated successfully!";
                // Refresh provider data
                $provider = $conn->query("SELECT * FROM users WHERE id = $providerId")->fetch_assoc();
            } else {
                $error = "Failed to update profile. Please try again.";
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'];
    $newPassword = $_POST['new_password'];
    $confirmPassword = $_POST['confirm_password'];
    
    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = "All password fields are required";
    } elseif ($newPassword !== $confirmPassword) {
        $error = "New passwords do not match";
    } elseif (strlen($newPassword) < 6) {
        $error = "Password must be at least 6 characters";
    } else {
        // Verify current password
        if (password_verify($currentPassword, $provider['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = '$hashedPassword' WHERE id = $providerId";
            if ($conn->query($sql)) {
                $success = "Password changed successfully!";
            } else {
                $error = "Failed to change password. Please try again.";
            }
        } else {
            $error = "Current password is incorrect";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - Smart Service Finder</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .simple-profile {
            max-width: 800px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .profile-header {
            text-align: center;
            margin-bottom: 3rem;
        }
        
        .profile-avatar {
            width: 100px;
            height: 100px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .profile-role {
            display: inline-block;
            background: var(--success-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            font-size: 0.875rem;
            font-weight: 500;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-card {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 1.5rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
        }
        
        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }
        
        .profile-sections {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
        }
        
        .section {
            background: white;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            padding: 2rem;
        }
        
        .section h3 {
            margin-bottom: 1.5rem;
            color: var(--text-primary);
            font-size: 1.25rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: var(--text-primary);
        }
        
        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid var(--border-color);
            border-radius: var(--radius);
            font-size: 0.875rem;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 100px;
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: var(--radius);
            font-size: 0.875rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }
        
        .btn-primary {
            background: var(--primary-color);
            color: white;
        }
        
        .btn-primary:hover {
            background: var(--primary-dark);
        }
        
        .btn-secondary {
            background: var(--secondary-color);
            color: white;
        }
        
        .btn-secondary:hover {
            background: #475569;
        }
        
        .alert {
            padding: 1rem;
            border-radius: var(--radius);
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: #d1fae5;
            color: #065f46;
            border: 1px solid #a7f3d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fca5a5;
        }
        
        @media (max-width: 768px) {
            .simple-profile {
                padding: 1rem;
            }
            
            .profile-sections {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }
        
        @media (max-width: 480px) {
            .stats-grid {
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
            <button class="mobile-menu-toggle" onclick="toggleMobileMenu()">☰</button>
            <ul class="navbar-nav">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="my_services.php">My Services</a></li>
                <li><a href="manage_bookings.php">Bookings</a></li>
                <li><a href="profile.php" class="active">Profile</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="flex">
        <!-- Sidebar -->
        <aside class="sidebar">
            <ul class="sidebar-nav">
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="my_services.php">🔧 My Services</a></li>
                <li><a href="manage_bookings.php">📅 Bookings</a></li>
                <li><a href="profile.php" class="active">👤 Profile</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="simple-profile">
                <!-- Profile Header -->
                <div class="profile-header">
                    <div class="profile-avatar">
                        <?php echo strtoupper(substr($provider['name'], 0, 1)); ?>
                    </div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($provider['name']); ?></h1>
                    <span class="profile-role">Professional Provider</span>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Statistics -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_services']; ?></div>
                        <div class="stat-label">Total Services</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['active_services']; ?></div>
                        <div class="stat-label">Active Services</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_bookings']; ?></div>
                        <div class="stat-label">Total Bookings</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['completed_bookings']; ?></div>
                        <div class="stat-label">Completed Jobs</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['pending_bookings']; ?></div>
                        <div class="stat-label">Pending Requests</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['total_reviews']; ?></div>
                        <div class="stat-label">Total Reviews</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number"><?php echo $stats['average_rating'] ? number_format($stats['average_rating'], 1) : '0.0'; ?></div>
                        <div class="stat-label">Average Rating</div>
                    </div>
                </div>

                <!-- Profile Sections -->
                <div class="profile-sections">
                    <!-- Profile Information -->
                    <div class="section">
                        <h3>Profile Information</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="update_profile">
                            
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($provider['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($provider['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($provider['phone'] ?: ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="address">Service Address</label>
                                <input type="text" id="address" name="address" value="<?php echo htmlspecialchars($provider['address'] ?: ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="description">Professional Description</label>
                                <textarea id="description" name="description" placeholder="Describe your services and expertise..."><?php echo htmlspecialchars($provider['description'] ?: ''); ?></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Update Profile</button>
                        </form>
                    </div>

                    <!-- Password Change -->
                    <div class="section">
                        <h3>Change Password</h3>
                        <form method="POST">
                            <input type="hidden" name="action" value="change_password">
                            
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <button type="submit" class="btn btn-secondary">Change Password</button>
                        </form>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
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

    <script src="../assets/js/main.js"></script>
</body>
</html>
