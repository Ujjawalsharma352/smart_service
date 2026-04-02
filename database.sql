-- Smart Local Service Finder Database Schema
-- Created for production-level service marketplace

CREATE DATABASE IF NOT EXISTS smart_service;
USE smart_service;

-- Users table (for all roles: user, provider, admin)
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('user', 'provider', 'admin') NOT NULL DEFAULT 'user',
    phone VARCHAR(20),
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table (providers add their services)
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    provider_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    category ENUM('plumber', 'tutor', 'electrician', 'carpenter', 'cleaner', 'painter', 'mechanic', 'other') NOT NULL,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Bookings table (users book services)
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider_id INT NOT NULL,
    service_id INT NOT NULL,
    booking_date DATE NOT NULL,
    time_slot VARCHAR(50),
    address TEXT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'completed', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (service_id) REFERENCES services(id) ON DELETE CASCADE
);

-- Reviews table (users review providers)
CREATE TABLE reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    provider_id INT NOT NULL,
    booking_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (provider_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
);

-- Insert default admin user (password: admin123)
INSERT INTO users (name, email, password, role, phone, address) VALUES 
('Admin User', 'admin@smartService.com', 'admin123', 'admin', '1234567890', 'Admin Office');

-- Insert sample users
INSERT INTO users (name, email, password, role, phone, address) VALUES 
('John Doe', 'john@example.com', 'user123', 'user', '9876543210', '123 Main St, City'),
('Jane Smith', 'jane@example.com', 'user123', 'user', '9876543211', '456 Oak Ave, City'),
('Mike Wilson', 'mike@example.com', 'provider123', 'provider', '9876543212', '789 Pine Rd, City'),
('Sarah Brown', 'sarah@example.com', 'provider123', 'provider', '9876543213', '321 Elm St, City');

-- Insert sample services
INSERT INTO services (provider_id, title, description, price, category) VALUES 
(3, 'Emergency Plumbing Service', 'Fix all types of plumbing issues including leaks, blockages, and installations', 150.00, 'plumber'),
(3, 'Bathroom Installation', 'Complete bathroom installation and renovation services', 500.00, 'plumber'),
(4, 'Math Tutoring', 'Expert math tutoring for high school students', 30.00, 'tutor'),
(4, 'Science Tutoring', 'Comprehensive science tutoring for all levels', 35.00, 'tutor'),
(3, 'Electrical Repair', 'Professional electrical repair and installation services', 120.00, 'electrician');
