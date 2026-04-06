-- Create notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role ENUM('user', 'provider', 'admin') NOT NULL,
    message TEXT NOT NULL,
    type ENUM('booking', 'status', 'system', 'info', 'success', 'error') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    booking_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    INDEX idx_user_role (user_id, role),
    INDEX idx_is_read (is_read),
    INDEX idx_created_at (created_at)
);

-- Add language column to users table
ALTER TABLE users ADD COLUMN IF NOT EXISTS language VARCHAR(10) DEFAULT 'en';

-- Create notification settings table
CREATE TABLE IF NOT EXISTS notification_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    email_notifications BOOLEAN DEFAULT TRUE,
    push_notifications BOOLEAN DEFAULT TRUE,
    booking_notifications BOOLEAN DEFAULT TRUE,
    status_notifications BOOLEAN DEFAULT TRUE,
    system_notifications BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user (user_id)
);

-- Insert sample notifications for testing
INSERT IGNORE INTO notifications (user_id, role, message, type, is_read, created_at) VALUES
(1, 'user', 'Welcome to Smart Service Finder!', 'system', FALSE, NOW()),
(1, 'user', 'Your booking request has been received', 'booking', FALSE, DATE_SUB(NOW(), INTERVAL 1 HOUR)),
(1, 'user', 'Your booking has been accepted', 'status', FALSE, DATE_SUB(NOW(), INTERVAL 2 HOUR)),
(1, 'user', 'Your booking has been completed', 'status', FALSE, DATE_SUB(NOW(), INTERVAL 3 HOUR));
