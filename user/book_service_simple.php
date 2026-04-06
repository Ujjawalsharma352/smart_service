<?php
require_once '../config/db.php';
require_once '../notifications/notification_functions.php';
require_once '../notifications/language_helper.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$userId = $_SESSION['user_id'];
$serviceId = (int)$_GET['id'];

// Get service details
$service = $conn->query("SELECT * FROM services WHERE id = $serviceId AND status = 'active'")->fetch_assoc();

if (!$service) {
    die("Service not found");
}

// Handle booking submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "<h2>PROCESSING BOOKING...</h2>";
    
    // Get form data
    $bookingDate = $_POST['booking_date'];
    $timeSlot = $_POST['time_slot'] ?? '';
    $address = $_POST['address'];
    
    echo "<p>Received data:</p>";
    echo "<p>Date: $bookingDate</p>";
    echo "<p>Time: $timeSlot</p>";
    echo "<p>Address: $address</p>";
    
    // Validate
    if (empty($bookingDate) || empty($address)) {
        echo "<p style='color: red;'>ERROR: Please fill in all required fields</p>";
    } else {
        // Insert booking - simple direct query
        $sql = "INSERT INTO bookings (user_id, provider_id, service_id, booking_date, time_slot, address, status) 
                VALUES ($userId, {$service['provider_id']}, $serviceId, '$bookingDate', '$timeSlot', '$address', 'pending')";
        
        echo "<p>SQL: $sql</p>";
        
        if ($conn->query($sql)) {
            $bookingId = $conn->insert_id;
            echo "<h3 style='color: green;'>✅ SUCCESS! Booking created with ID: $bookingId</h3>";
            echo "<div id='booking-status' class='booking-status pending'>Status: PENDING</div>";
            echo "<p style='color: blue;'>📧 Your booking is being processed. The provider will confirm shortly.</p>";
            echo "<p style='color: gray;'>🔄 This page will automatically update when the status changes.</p>";
            
            // Create notifications for user, provider, and admin
            createBookingNotifications($bookingId, $userId, $service['provider_id'], $service['title'], 'pending');
            
            // Verify booking was inserted
            $verify = $conn->query("SELECT * FROM bookings WHERE id = $bookingId")->fetch_assoc();
            if ($verify) {
                echo "<p style='color: blue;'>✅ Booking verified in database!</p>";
                echo "<script>startStatusMonitoring($bookingId);</script>";
            } else {
                echo "<p style='color: red;'>❌ Booking not found after insertion</p>";
            }
            
        } else {
            echo "<p style='color: red;'>❌ ERROR: " . $conn->error . "</p>";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Simple Booking Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 600px; margin: 0 auto; }
        .service-info { background: #f5f5f5; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; font-weight: bold; }
        input, textarea, select { width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        button { padding: 12px 24px; background: #007bff; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
        button:hover { background: #0056b3; }
        .success { color: green; background: #d4edda; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .error { color: red; background: #f8d7da; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .loading { color: #666; background: #fff3cd; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .notification { position: fixed; top: 20px; right: 20px; padding: 15px; border-radius: 4px; color: white; z-index: 1000; }
        .notification.success { background: #28a745; }
        .notification.error { background: #dc3545; }
        .notification.info { background: #17a2b8; }
        .booking-status { padding: 10px; border-radius: 4px; margin: 10px 0; font-weight: bold; }
        .booking-status.pending { background: #fff3cd; color: #856404; }
        .booking-status.confirmed { background: #d4edda; color: #155724; }
        .booking-status.completed { background: #cce5ff; color: #004085; }
        .booking-status.cancelled { background: #f8d7da; color: #721c24; }
    </style>
    <script>
        // Dynamic booking status checker
        let bookingStatusInterval;
        let currentBookingId = null;
        
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 5000);
        }
        
        function updateBookingStatus(bookingId, status) {
            const statusElement = document.getElementById('booking-status');
            if (statusElement) {
                statusElement.className = `booking-status ${status}`;
                statusElement.textContent = `Status: ${status.toUpperCase()}`;
                showNotification(`Booking status updated to: ${status}`, 'info');
            }
        }
        
        function checkBookingStatus(bookingId) {
            fetch(`../api/bookings/status.php?id=${bookingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.status) {
                        updateBookingStatus(bookingId, data.status);
                    }
                })
                .catch(error => {
                    console.log('Status check error:', error);
                });
        }
        
        function startStatusMonitoring(bookingId) {
            currentBookingId = bookingId;
            bookingStatusInterval = setInterval(() => {
                checkBookingStatus(bookingId);
            }, 10000); // Check every 10 seconds
        }
        
        function stopStatusMonitoring() {
            if (bookingStatusInterval) {
                clearInterval(bookingStatusInterval);
            }
        }
        
        // Dynamic form validation
        function validateForm() {
            const date = document.getElementById('booking_date').value;
            const address = document.getElementById('address').value;
            const today = new Date().toISOString().split('T')[0];
            
            if (date < today) {
                showNotification('Booking date cannot be in the past!', 'error');
                return false;
            }
            
            if (address.length < 10) {
                showNotification('Address must be at least 10 characters!', 'error');
                return false;
            }
            
            return true;
        }
        
        // Auto-fill time slots based on date
        function updateTimeSlots() {
            const date = document.getElementById('booking_date').value;
            const timeSelect = document.getElementById('time_slot');
            
            if (date) {
                // Show loading state
                timeSelect.innerHTML = '<option value="">Loading available slots...</option>';
                
                // Simulate API call (in real app, this would fetch from server)
                setTimeout(() => {
                    const slots = ['09:00 AM', '10:00 AM', '11:00 AM', '02:00 PM', '03:00 PM', '04:00 PM'];
                    timeSelect.innerHTML = '<option value="">Select a time</option>';
                    slots.forEach(slot => {
                        timeSelect.innerHTML += `<option value="${slot}">${slot}</option>`;
                    });
                }, 500);
            }
        }
        
        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            const dateInput = document.getElementById('booking_date');
            if (dateInput) {
                dateInput.addEventListener('change', updateTimeSlots);
                dateInput.min = new Date().toISOString().split('T')[0];
            }
            
            const form = document.getElementById('booking-form');
            if (form) {
                form.addEventListener('submit', function(e) {
                    if (!validateForm()) {
                        e.preventDefault();
                    }
                });
            }
        });
    </script>
</head>
<body>
    <div class="container">
        <h1>🔧 Simple Booking Form</h1>
        
        <div class="service-info">
            <h2><?php echo htmlspecialchars($service['title']); ?></h2>
            <p><strong>Category:</strong> <?php echo htmlspecialchars($service['category']); ?></p>
            <p><strong>Price:</strong> $<?php echo number_format($service['price'], 2); ?></p>
            <p><strong>Provider:</strong> <?php echo htmlspecialchars($service['provider_id']); ?></p>
            <p><?php echo htmlspecialchars($service['description']); ?></p>
        </div>
        
        <form method="POST" id="booking-form">
            <h3>Book This Service</h3>
            
            <div class="form-group">
                <label for="booking_date">Booking Date *</label>
                <input type="date" id="booking_date" name="booking_date" required 
                       value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                       min="<?php echo date('Y-m-d'); ?>">
                <small>Select a date to see available time slots</small>
            </div>
            
            <div class="form-group">
                <label for="time_slot">Preferred Time</label>
                <select id="time_slot" name="time_slot">
                    <option value="">Select a date first</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="address">Service Address *</label>
                <textarea id="address" name="address" rows="3" required 
                          placeholder="Enter the complete address where the service should be provided..."></textarea>
                <small>Minimum 10 characters required</small>
            </div>
            
            <button type="submit">📅 Book Now</button>
        </form>
        
        <div style="margin-top: 30px;">
            <a href="services.php">← Back to Services</a> |
            <a href="my_bookings.php">View My Bookings</a>
        </div>
    </div>
</body>
</html>
