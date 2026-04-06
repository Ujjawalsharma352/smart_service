<?php
require_once '../config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Global database connection
global $conn;

// Set language
function setLanguage($lang) {
    $_SESSION['lang'] = $lang;
}

// Create notification
function createNotification($userId, $role, $message, $type = 'info', $bookingId = null) {
    global $conn;

    if ($bookingId !== null) {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, role, message, type, booking_id, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("isssi", $userId, $role, $message, $type, $bookingId);
    } else {
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, role, message, type, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->bind_param("isss", $userId, $role, $message, $type);
    }
    return $stmt->execute();
}

// Create booking notifications (new, accepted, rejected, completed, cancelled)
function createBookingNotifications($bookingId, $userId, $providerId, $serviceTitle, $status = 'pending') {
    global $conn;
    
    // Load language
    $lang_file = "../lang/" . ($_SESSION['lang'] ?? 'en') . ".php";
    if (file_exists($lang_file)) {
        include $lang_file;
    } else {
        include '../lang/en.php';
    }
    
    $userMessage = '';
    $providerMessage = '';
    $notificationType = 'info';
    
    switch ($status) {
        case 'pending':
            $userMessage = $lang['booking_pending'] ?? 'Your booking is pending';
            $providerMessage = $lang['provider_booking_received'] ?? 'New booking request received';
            $notificationType = 'info';
            break;
        case 'accepted':
            $userMessage = $lang['booking_accepted'] ?? 'Your booking has been accepted';
            $providerMessage = $lang['provider_booking_accepted'] ?? 'Booking has been accepted';
            $notificationType = 'success';
            break;
        case 'rejected':
            $userMessage = $lang['booking_rejected'] ?? 'Your booking was rejected';
            $providerMessage = $lang['provider_booking_rejected'] ?? 'Booking has been rejected';
            $notificationType = 'error';
            break;
        case 'completed':
            $userMessage = $lang['booking_completed'] ?? 'Your booking has been completed';
            $providerMessage = $lang['provider_booking_completed'] ?? 'Booking has been completed';
            $notificationType = 'success';
            break;
        case 'cancelled':
            $userMessage = $lang['booking_cancelled'] ?? 'Your booking has been cancelled';
            $providerMessage = $lang['provider_booking_cancelled'] ?? 'Booking has been cancelled';
            $notificationType = 'error';
            break;
        default:
            $userMessage = $lang['booking_pending'] ?? 'Your booking is pending';
            $providerMessage = $lang['provider_booking_received'] ?? 'New booking request received';
            $notificationType = 'info';
            break;
    }
    
    // Send to user
    createNotification($userId, 'user', $userMessage, $notificationType, $bookingId);
    
    // Send to provider
    createNotification($providerId, 'provider', $providerMessage, $notificationType, $bookingId);
    
    // Notify admins
    $adminUsers = $conn->query("SELECT id FROM users WHERE role = 'admin'");
    while ($admin = $adminUsers->fetch_assoc()) {
        createNotification($admin['id'], 'admin', $providerMessage, $notificationType, $bookingId);
    }
}

// Create status update notifications
function createStatusUpdateNotifications($bookingId, $status, $userId, $providerId) {
    global $conn;
    
    // Load language
    $lang_file = "../lang/" . ($_SESSION['lang'] ?? 'en') . ".php";
    if (file_exists($lang_file)) {
        include $lang_file;
    } else {
        include '../lang/en.php';
    }
    
    $messageKey = 'booking_' . $status;
    $userMessage = $lang[$messageKey] ?? "Booking status updated to: " . $status;
    $providerMessageKey = 'provider_booking_' . $status;
    $providerMessage = $lang[$providerMessageKey] ?? $userMessage;
    
    // Send to user
    createNotification($userId, 'user', $userMessage, 'status', $bookingId);
    
    // Send to provider
    createNotification($providerId, 'provider', $providerMessage, 'status', $bookingId);
}

// Get notifications
function getNotifications($userId, $role, $limit = 10) {
    global $conn;
    
    if ($limit === null || $limit <= 0) {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND role = ? ORDER BY created_at DESC");
        $stmt->bind_param("is", $userId, $role);
    } else {
        $stmt = $conn->prepare("SELECT * FROM notifications WHERE user_id = ? AND role = ? ORDER BY created_at DESC LIMIT ?");
        $stmt->bind_param("isi", $userId, $role, $limit);
    }
    $stmt->execute();
    return $stmt->get_result();
}

// Get unread count
function getUnreadCount($userId, $role) {
    global $conn;
    
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND role = ? AND is_read = 0");
    $stmt->bind_param("is", $userId, $role);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc()['count'];
}

// Mark as read
function markAsRead($notificationId, $userId) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

// Delete a single notification
function deleteNotification($notificationId, $userId) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notificationId, $userId);
    return $stmt->execute();
}

// Mark all as read
function markAllAsRead($userId, $role) {
    global $conn;
    
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1, updated_at = NOW() WHERE user_id = ? AND role = ? AND is_read = 0");
    $stmt->bind_param("is", $userId, $role);
    return $stmt->execute();
}

// Delete old notifications
function deleteOldNotifications($days = 30) {
    global $conn;
    
    $stmt = $conn->prepare("DELETE FROM notifications WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)");
    $stmt->bind_param("i", $days);
    return $stmt->execute();
}

// Format time ago
function timeAgo($datetime) {
    $time = new DateTime($datetime);
    $now = new DateTime();
    $diff = $now->getTimestamp() - $time->getTimestamp();
    
    if ($diff < 60) {
        return 'just now';
    } else if ($diff < 3600) {
        $minutes = floor($diff / 60);
        return $minutes . ' minutes ago';
    } else if ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hours ago';
    } else if ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' days ago';
    } else {
        $weeks = floor($diff / 604800);
        return $weeks . ' weeks ago';
    }
}
?>
