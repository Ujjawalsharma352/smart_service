<?php
require_once '../config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = APIInput::getJSON();
    
    if (!APIInput::validate($data, ['name', 'email', 'password', 'role'])) {
        exit;
    }
    
    $name = clean_input($data['name']);
    $email = clean_input($data['email']);
    $password = $data['password'];
    $role = clean_input($data['role']);
    $phone = clean_input($data['phone'] ?? '');
    $address = clean_input($data['address'] ?? '');
    
    // Validate role
    if (!in_array($role, ['user', 'provider'])) {
        APIResponse::error('Invalid role. Must be user or provider');
        exit;
    }
    
    // Check if email already exists
    $existing = $conn->query("SELECT id FROM users WHERE email = '$email'")->fetch_assoc();
    if ($existing) {
        APIResponse::error('Email already exists');
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert user
    $sql = "INSERT INTO users (name, email, password, role, phone, address) 
            VALUES ('$name', '$email', '$hashedPassword', '$role', '$phone', '$address')";
    
    if ($conn->query($sql)) {
        $userId = $conn->insert_id;
        
        // Get created user
        $user = $conn->query("SELECT id, name, email, role, phone, address FROM users WHERE id = $userId")->fetch_assoc();
        
        APIResponse::success([
            'user' => $user
        ], 'Registration successful');
    } else {
        APIResponse::error('Registration failed: ' . $conn->error);
    }
} else {
    APIResponse::error('Method not allowed', 405);
}
?>
