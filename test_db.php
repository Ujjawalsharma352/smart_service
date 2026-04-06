<?php
// Database Connection Test
echo "<h1>Database Connection Test</h1>";

try {
    require_once 'config/db.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        echo "<p style='color: green;'>✅ Database connection successful!</p>";

        // Test if tables exist
        $tables = ['users', 'services', 'bookings', 'reviews'];
        foreach ($tables as $table) {
            $result = $conn->query("SHOW TABLES LIKE '$table'");
            if ($result && $result->num_rows > 0) {
                echo "<p style='color: green;'>✅ Table '$table' exists</p>";
            } else {
                echo "<p style='color: red;'>❌ Table '$table' missing</p>";
            }
        }

        // Test basic query
        $result = $conn->query("SELECT COUNT(*) as count FROM users");
        if ($result) {
            $count = $result->fetch_assoc()['count'];
            echo "<p style='color: green;'>✅ Users table query successful: $count users</p>";
        }

    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>";
}

echo "<br><a href='index.php'>Back to Homepage</a>";
?>