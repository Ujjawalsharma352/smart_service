<?php
require_once '../config/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);
    $confirm_password = clean_input($_POST['confirm_password']);
    $role = clean_input($_POST['role']);
    $phone = clean_input($_POST['phone']);
    $address = clean_input($_POST['address']);
    
    // Validation
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters long";
    } else {
        // Check if email already exists
        $check_sql = "SELECT id FROM users WHERE email = '$email'";
        $check_result = $conn->query($check_sql);
        
        if ($check_result && $check_result->num_rows > 0) {
            $error = "Email address already registered";
        } else {
            // Insert new user (plain text for now)
            $sql = "INSERT INTO users (name, email, password, role, phone, address) 
                    VALUES ('$name', '$email', '$password', '$role', '$phone', '$address')";
            
            if ($conn->query($sql)) {
                $success = "Registration successful! Please login.";
                // Clear form data
                $name = $email = $phone = $address = '';
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Smart Service Finder</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>🔧 Smart Service Finder</h1>
                <h2>Create New Account</h2>
            </div>
            
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
            
            <form method="POST" class="auth-form">
                <div class="form-row">
                    <div class="form-group">
                        <label for="name">Full Name *</label>
                        <input type="text" id="name" name="name" required 
                               value="<?php echo isset($name) ? htmlspecialchars($name) : ''; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="email">Email Address *</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="password">Password *</label>
                        <input type="password" id="password" name="password" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password *</label>
                        <input type="password" id="confirm_password" name="confirm_password" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="role">Register As *</label>
                    <select id="role" name="role" required>
                        <option value="">Select Role</option>
                        <option value="user" <?php echo (isset($role) && $role == 'user') ? 'selected' : ''; ?>>Customer</option>
                        <option value="provider" <?php echo (isset($role) && $role == 'provider') ? 'selected' : ''; ?>>Service Provider</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="phone">Phone Number</label>
                    <input type="tel" id="phone" name="phone" 
                           value="<?php echo isset($phone) ? htmlspecialchars($phone) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" rows="3"><?php echo isset($address) ? htmlspecialchars($address) : ''; ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Register Account</button>
            </form>
            
            <div class="auth-footer">
                <p>Already have an account? <a href="login.php">Login here</a></p>
                <p><a href="../index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
