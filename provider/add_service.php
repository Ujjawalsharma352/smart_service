<?php
require_once '../config/db.php';
requireRole('provider');

$providerId = getUserId();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Debug: Log POST data
    error_log("Add Service POST data: " . print_r($_POST, true));
    
    $title = clean_input($_POST['title']);
    $description = clean_input($_POST['description']);
    $price = clean_input($_POST['price']);
    $category = clean_input($_POST['category']);
    
    // Debug: Log cleaned data
    error_log("Cleaned data - Title: $title, Price: $price, Category: $category");
    
    // Validation
    if (empty($title) || empty($description) || empty($price) || empty($category)) {
        $error = "Please fill in all required fields";
        error_log("Validation failed: Missing required fields");
    } elseif (!is_numeric($price) || $price <= 0) {
        $error = "Please enter a valid price";
        error_log("Validation failed: Invalid price - $price");
    } else {
        // Insert service - Fixed SQL with proper quotes around price
        $sql = "INSERT INTO services (provider_id, title, description, price, category, status, created_at) 
                VALUES ($providerId, '$title', '$description', '$price', '$category', 'active', NOW())";
        
        // Debug: Log the SQL query
        error_log("Service SQL: " . $sql);
        
        if ($conn->query($sql)) {
            $success = "Service added successfully!";
            error_log("Service added successfully");
            // Clear form data
            $_POST = array();
        } else {
            $error = "Failed to add service. Error: " . $conn->error;
            error_log("Service add failed: " . $conn->error);
        }
    }
}

// Get provider's existing services
$myServices = $conn->query("
    SELECT * FROM services 
    WHERE provider_id = $providerId 
    ORDER BY created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Service - Smart Service Finder</title>
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
                <li><a href="add_service.php" class="active">Add Service</a></li>
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
                <li><a href="add_service.php" class="active">➕ Add Service</a></li>
                <li><a href="manage_bookings.php">📅 Manage Bookings</a></li>
                <li><a href="my_services.php">🔧 My Services</a></li>
                <li><a href="profile.php">👤 Profile</a></li>
                <li><a href="../auth/logout.php">🚪 Logout</a></li>
            </ul>
        </aside>

        <!-- Main Content -->
        <main class="main-content">
            <div class="page-header">
                <h1 class="page-title">Add New Service</h1>
                <p class="page-subtitle">Create a new service offering for customers</p>
            </div>

            <div class="grid grid-cols-3 gap-8">
                <!-- Add Service Form -->
                <div class="col-span-2">
                    <div class="card">
                        <div class="card-header">
                            <h3>Service Details</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($error): ?>
                                <div class="alert alert-error">
                                    <?php echo $error; ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($success): ?>
                                <div class="alert alert-success">
                                    <?php echo $success; ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" id="serviceForm">
                                <div class="form-group">
                                    <label for="title">Service Title *</label>
                                    <input type="text" id="title" name="title" required 
                                           value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>"
                                           placeholder="e.g., Emergency Plumbing Repair">
                                </div>
                                
                                <div class="form-row">
                                    <div class="form-group">
                                        <label for="category">Category *</label>
                                        <select id="category" name="category" required>
                                            <option value="">Select Category</option>
                                            <option value="plumber" <?php echo (isset($_POST['category']) && $_POST['category'] == 'plumber') ? 'selected' : ''; ?>>Plumber</option>
                                            <option value="tutor" <?php echo (isset($_POST['category']) && $_POST['category'] == 'tutor') ? 'selected' : ''; ?>>Tutor</option>
                                            <option value="electrician" <?php echo (isset($_POST['category']) && $_POST['category'] == 'electrician') ? 'selected' : ''; ?>>Electrician</option>
                                            <option value="carpenter" <?php echo (isset($_POST['category']) && $_POST['category'] == 'carpenter') ? 'selected' : ''; ?>>Carpenter</option>
                                            <option value="cleaner" <?php echo (isset($_POST['category']) && $_POST['category'] == 'cleaner') ? 'selected' : ''; ?>>Cleaner</option>
                                            <option value="painter" <?php echo (isset($_POST['category']) && $_POST['category'] == 'painter') ? 'selected' : ''; ?>>Painter</option>
                                            <option value="mechanic" <?php echo (isset($_POST['category']) && $_POST['category'] == 'mechanic') ? 'selected' : ''; ?>>Mechanic</option>
                                            <option value="other" <?php echo (isset($_POST['category']) && $_POST['category'] == 'other') ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="price">Price ($) *</label>
                                        <input type="number" id="price" name="price" required 
                                               min="0" step="0.01"
                                               value="<?php echo isset($_POST['price']) ? htmlspecialchars($_POST['price']) : ''; ?>"
                                               placeholder="e.g., 150.00">
                                    </div>
                                </div>
                                
                                <div class="form-group">
                                    <label for="description">Description *</label>
                                    <textarea id="description" name="description" required rows="6"
                                              placeholder="Provide a detailed description of your service, including what's included, your expertise, and any special requirements..."><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">Add Service</button>
                                    <a href="dashboard.php" class="btn btn-secondary">Cancel</a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Side Panel -->
                <div>
                    <!-- Service Guidelines -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Service Guidelines</h3>
                        </div>
                        <div class="card-body">
                            <h4 class="mb-4">Best Practices:</h4>
                            <ul class="list-unstyled">
                                <li class="mb-3">
                                    <strong>📝 Clear Title:</strong> Use descriptive titles that clearly explain what you offer
                                </li>
                                <li class="mb-3">
                                    <strong>💰 Competitive Pricing:</strong> Research market rates for your services
                                </li>
                                <li class="mb-3">
                                    <strong>📋 Detailed Description:</strong> Include scope, duration, materials, and expertise
                                </li>
                                <li class="mb-3">
                                    <strong>🏷️ Right Category:</strong> Choose the most appropriate category for better visibility
                                </li>
                                <li class="mb-3">
                                    <strong>⭐  Quality Photos:</strong> Add clear images of your work (coming soon)
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Recent Services -->
                    <div class="card mt-6">
                        <div class="card-header">
                            <h3>Your Recent Services</h3>
                        </div>
                        <div class="card-body">
                            <?php if ($myServices->num_rows > 0): ?>
                                <div class="space-y-4">
                                    <?php while ($service = $myServices->fetch_assoc()): ?>
                                        <div class="border-bottom pb-3">
                                            <h5 class="font-medium"><?php echo htmlspecialchars($service['title']); ?></h5>
                                            <div class="flex justify-between items-center mt-1">
                                                <span class="service-category"><?php echo htmlspecialchars($service['category']); ?></span>
                                                <span class="service-price">$<?php echo number_format($service['price'], 2); ?></span>
                                            </div>
                                            <span class="badge badge-<?php echo $service['status']; ?>">
                                                <?php echo ucfirst($service['status']); ?>
                                            </span>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
                            <?php else: ?>
                                <p class="text-secondary text-center">No services added yet</p>
                            <?php endif; ?>
                        </div>
                        <?php if ($myServices->num_rows > 0): ?>
                            <div class="card-footer">
                                <a href="my_services.php" class="btn btn-secondary btn-sm">View All Services</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <style>
        .space-y-4 > * + * {
            margin-top: 1rem;
        }
        
        .list-unstyled {
            list-style: none;
            padding: 0;
        }
        
        .border-bottom {
            border-bottom: 1px solid var(--border-color);
        }
    </style>

    <script src="../assets/js/main.js"></script>
</body>
</html>
