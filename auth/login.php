<?php
require_once '../config/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = clean_input($_POST['email']);
    $password = clean_input($_POST['password']);
    
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    } else {
        // Check user credentials (direct database access)
        $sql = "SELECT * FROM users WHERE email = '$email'";
        $result = $conn->query($sql);
        
        if ($result && $result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Check password (handle both plain text and hashed)
            $passwordValid = false;
            
            // Check if password is hashed
            if (password_get_info($user['password'])['algo'] !== null) {
                // Hashed password - use password_verify
                $passwordValid = password_verify($password, $user['password']);
            } else {
                // Plain text password - compare directly
                $passwordValid = ($user['password'] === $password);
            }
            
            if ($passwordValid) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Redirect based on role
                switch ($user['role']) {
                    case 'admin':
                        redirect('../admin/dashboard.php');
                        break;
                    case 'provider':
                        redirect('../provider/dashboard.php');
                        break;
                    case 'user':
                        redirect('../user/dashboard.php');
                        break;
                    default:
                        redirect('../index.php');
                        break;
                }
            } else {
                $error = "Invalid email or password";
            }
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Smart Service Finder</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Smart Service Finder</h1>
                <h2>Login to Your Account</h2>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form">
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required 
                           value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">Login</button>
            </form>
            
            <div class="auth-footer">
                <p>Don't have an account? <a href="register.php">Register here</a></p>
                <p><a href="../index.php">← Back to Home</a></p>
            </div>
        </div>
    </div>
</body>
</html>
