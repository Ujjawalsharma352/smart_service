<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = APIInput::getJSON();
    
    if (!APIInput::validate($data, ['email', 'password'])) {
        exit;
    }
    
    $email = clean_input($data['email']);
    $password = $data['password'];
    
    // Get user from database
    $user = $conn->query("SELECT * FROM users WHERE email = '$email'")->fetch_assoc();
    
    if ($user) {
        $passwordValid = false;
        
        // Check if password is hashed
        if (password_get_info($user['password'])['algo'] !== null) {
            // Hashed password - use password_verify
            $passwordValid = password_verify($password, $user['password']);
        } else {
            // Plain text password - compare directly and migrate
            if ($user['password'] === $password) {
                $passwordValid = true;
                
                // Migrate to hashed password
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $conn->query("UPDATE users SET password = '$hashedPassword' WHERE id = " . $user['id']);
            }
        }
        
        if ($passwordValid) {
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            
            // Return user data (without password)
            unset($user['password']);
            
            APIResponse::success([
                'user' => $user,
                'session_id' => session_id()
            ], 'Login successful');
        } else {
            APIResponse::error('Invalid email or password');
        }
    } else {
        APIResponse::error('Invalid email or password');
    }
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
