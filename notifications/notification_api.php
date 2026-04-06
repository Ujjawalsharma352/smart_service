<?php
require_once '../config/db.php';
require_once 'notification_functions.php';

header('Content-Type: application/json');

// Get action
$action = $_GET['action'] ?? $_POST['action'];

try {
    switch ($action) {
        case 'create_booking_notification':
            $userId = $_POST['user_id'] ?? 0;
            $providerId = $_POST['provider_id'] ?? 0;
            $serviceTitle = $_POST['service_title'] ?? '';
            $status = $_POST['status'] ?? 'pending';
            $bookingId = $_POST['booking_id'] ?? 0;
            
            if (createBookingNotifications($bookingId, $userId, $providerId, $serviceTitle, $status)) {
                echo json_encode(['success' => true, 'message' => 'Booking notifications created']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create booking notification']);
            }
            break;
            
        case 'status_update':
            $bookingId = $_POST['booking_id'] ?? 0;
            $status = $_POST['status'] ?? '';
            $userId = $_POST['user_id'] ?? 0;
            $providerId = $_POST['provider_id'] ?? 0;
            
            createStatusUpdateNotifications($bookingId, $status, $userId, $providerId);
            
            echo json_encode(['success' => true, 'message' => 'Status update notifications created']);
            break;
            
        case 'fetch':
        case 'get_notifications':
            $userId = $_GET['user_id'] ?? 0;
            $role = $_GET['role'] ?? $_GET['user_role'] ?? 'user';
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : null;
            
            $notifications = getNotifications($userId, $role, $limit);
            $unreadCount = getUnreadCount($userId, $role);
            
            $notificationList = [];
            while ($notification = $notifications->fetch_assoc()) {
                $notificationList[] = [
                    'id' => $notification['id'],
                    'message' => $notification['message'],
                    'type' => $notification['type'],
                    'is_read' => $notification['is_read'],
                    'created_at' => $notification['created_at'],
                    'time_ago' => timeAgo($notification['created_at'])
                ];
            }
            
            echo json_encode([
                'success' => true,
                'notifications' => $notificationList,
                'unread_count' => $unreadCount
            ]);
            break;
            
        case 'mark_read':
            $notificationId = $_POST['notification_id'] ?? $_GET['notification_id'] ?? $_GET['id'] ?? 0;
            $userId = $_POST['user_id'] ?? $_GET['user_id'] ?? 0;
            
            if (markAsRead($notificationId, $userId)) {
                echo json_encode(['success' => true, 'message' => 'Notification marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark as read']);
            }
            break;
            
        case 'mark_all_read':
            $userId = $_POST['user_id'] ?? $_GET['user_id'] ?? 0;
            $role = $_POST['role'] ?? $_GET['role'] ?? $_GET['user_role'] ?? 'user';
            
            if (markAllAsRead($userId, $role)) {
                echo json_encode(['success' => true, 'message' => 'All notifications marked as read']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to mark all as read']);
            }
            break;
            
        case 'delete':
            $notificationId = $_POST['notification_id'] ?? $_GET['notification_id'] ?? 0;
            $userId = $_POST['user_id'] ?? $_GET['user_id'] ?? 0;
            
            if (deleteNotification($notificationId, $userId)) {
                echo json_encode(['success' => true, 'message' => 'Notification deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete notification']);
            }
            break;
            
        case 'create':
            $userId = $_POST['user_id'] ?? 0;
            $role = $_POST['user_role'] ?? $_POST['role'] ?? 'user';
            $message = $_POST['message'] ?? '';
            $type = $_POST['type'] ?? 'info';
            $relatedId = $_POST['related_id'] ?? null;
            
            if (createNotification($userId, $role, $message, $type, $relatedId)) {
                echo json_encode(['success' => true, 'message' => 'Notification created']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create notification']);
            }
            break;
            
        case 'delete_old':
            $days = $_POST['days'] ?? 30;
            
            if (deleteOldNotifications($days)) {
                echo json_encode(['success' => true, 'message' => 'Old notifications deleted']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete old notifications']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
