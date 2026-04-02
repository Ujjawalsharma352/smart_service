<?php
require_once '../config/db.php';
requireRole('user');

$userId = getUserId();

// Get user information
$user = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();

// Get user's booking statistics
$stats = [
    'total' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId")->fetch_assoc()['count'],
    'pending' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId AND status = 'pending'")->fetch_assoc()['count'],
    'completed' => $conn->query("SELECT COUNT(*) as count FROM bookings WHERE user_id = $userId AND status = 'completed'")->fetch_assoc()['count']
];

// Handle profile update
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    
    // Validation
    if (empty($name) || empty($email)) {
        $error = "Name and email are required";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Invalid email format";
    } else {
        // Check if email is already taken by another user
        $checkEmail = $conn->query("SELECT id FROM users WHERE email = '$email' AND id != $userId")->fetch_assoc();
        if ($checkEmail) {
            $error = "Email is already taken by another user";
        } else {
            // Update profile
            $sql = "UPDATE users SET name = '$name', email = '$email', phone = '$phone', address = '$address' WHERE id = $userId";
            if ($conn->query($sql)) {
                $success = "Profile updated successfully!";
                // Refresh user data
                $user = $conn->query("SELECT * FROM users WHERE id = $userId")->fetch_assoc();
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
        if (password_verify($currentPassword, $user['password'])) {
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $sql = "UPDATE users SET password = '$hashedPassword' WHERE id = $userId";
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
            width: 80px;
            height: 80px;
            background: #3b82f6;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
            margin: 0 auto 1rem;
        }
        
        .profile-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .profile-email {
            color: #666;
            margin-bottom: 0.5rem;
        }
        
        .profile-member-since {
            color: #999;
            font-size: 0.9rem;
        }
        
        .simple-card {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #333;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 0.5rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #333;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #d1d5db;
            border-radius: 0.375rem;
            font-size: 1rem;
        }
        
        .form-control:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
        }
        
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 0.375rem;
            font-size: 1rem;
            font-weight: 500;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background: #2563eb;
        }
        
        .btn-secondary {
            background: #f3f4f6;
            color: #374151;
            border: 1px solid #d1d5db;
        }
        
        .btn-secondary:hover {
            background: #e5e7eb;
        }
        
        .stats-row {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-bottom: 2rem;
        }
        
        .stat-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
        }
        
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #3b82f6;
            margin-bottom: 0.25rem;
        }
        
        .stat-label {
            font-size: 0.875rem;
            color: #64748b;
        }
        
        .alert {
            padding: 1rem;
            border-radius: 0.375rem;
            margin-bottom: 1.5rem;
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
        
        @media (max-width: 768px) {
            .simple-profile {
                padding: 1rem;
            }
            
            .stats-row {
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
                <li><a href="services.php">Services</a></li>
                <li><a href="my_bookings.php">My Bookings</a></li>
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
                <li><a href="services.php">🔧 Browse Services</a></li>
                <li><a href="my_bookings.php">📅 My Bookings</a></li>
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
                        <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                    </div>
                    <h1 class="profile-name"><?php echo htmlspecialchars($user['name']); ?></h1>
                    <p class="profile-email"><?php echo htmlspecialchars($user['email']); ?></p>
                    <p class="profile-member-since">Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>

                <!-- Success/Error Messages -->
                <?php if ($success): ?>
                    <div class="alert alert-success">
                        ✅ <?php echo $success; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        ❌ <?php echo $error; ?>
                    </div>
                <?php endif; ?>

                <!-- Booking Statistics -->
                <div class="simple-card">
                    <h2 class="card-title">📊 Your Booking Summary</h2>
                    <div class="stats-row">
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['total']; ?></div>
                            <div class="stat-label">Total Bookings</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['completed']; ?></div>
                            <div class="stat-label">Completed</div>
                        </div>
                        <div class="stat-box">
                            <div class="stat-number"><?php echo $stats['pending']; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                </div>

                <!-- Personal Information -->
                <div class="simple-card">
                    <h2 class="card-title">👤 Personal Information</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="update_profile">
                        
                        <div class="form-group">
                            <label for="name">Full Name *</label>
                            <input type="text" id="name" name="name" required 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($user['name']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email Address *</label>
                            <input type="email" id="email" name="email" required 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($user['email']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   class="form-control"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="address">Address</label>
                            <textarea id="address" name="address" rows="3"
                                      class="form-control"
                                      placeholder="Enter your address"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            💾 Update Profile
                        </button>
                    </form>
                </div>

                <!-- Password Change -->
                <div class="simple-card">
                    <h2 class="card-title">🔒 Change Password</h2>
                    <form method="POST">
                        <input type="hidden" name="action" value="change_password">
                        
                        <div class="form-group">
                            <label for="current_password">Current Password *</label>
                            <input type="password" id="current_password" name="current_password" required 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password *</label>
                            <input type="password" id="new_password" name="new_password" required 
                                   class="form-control">
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password *</label>
                            <input type="password" id="confirm_password" name="confirm_password" required 
                                   class="form-control">
                        </div>
                        
                        <div style="display: flex; gap: 1rem;">
                            <button type="submit" class="btn btn-secondary">
                                🔐 Change Password
                            </button>
                            <a href="dashboard.php" class="btn btn-secondary">
                                ← Back to Dashboard
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script src="../assets/js/main.js"></script>
</body>
</html>
