<?php
// Server Information for Debugging
echo "<h1>Server Information</h1>";
echo "<h2>PHP Version: " . phpversion() . "</h2>";

// Check MySQL extension
if (extension_loaded('mysqli')) {
    echo "<p style='color: green;'>✅ MySQLi extension is loaded</p>";
} else {
    echo "<p style='color: red;'>❌ MySQLi extension is NOT loaded</p>";
}

// Check database connection
echo "<h2>Database Connection Test</h2>";
try {
    require_once 'config/db.php';
    $database = new Database();
    $conn = $database->getConnection();

    if ($conn) {
        echo "<p style='color: green;'>✅ Database connection successful!</p>";

        // Test basic query
        $result = $conn->query("SELECT 1");
        if ($result) {
            echo "<p style='color: green;'>✅ Basic query successful</p>";
        } else {
            echo "<p style='color: red;'>❌ Basic query failed: " . $conn->error . "</p>";
        }

    } else {
        echo "<p style='color: red;'>❌ Database connection failed</p>";
    }

} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database Error: " . $e->getMessage() . "</p>";
}

// Check file permissions
echo "<h2>File Permissions</h2>";
$files_to_check = [
    'config/db.php',
    'index.php',
    'database.sql'
];

foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $perms = substr(sprintf('%o', fileperms($file)), -4);
        echo "<p>$file: $perms " . (is_readable($file) ? '(readable)' : '(not readable)') . "</p>";
    } else {
        echo "<p style='color: red;'>$file: File not found</p>";
    }
}

echo "<br><a href='index.php'>Back to Homepage</a>";
?>