<?php
// Database Configuration for Smart Local Service Finder

class Database {
    private $host = "ftpupload.net";
    private $username = "if0_41552769";
    private $password = "udddOIB7vEW3H";
    private $dbname = "if0_41552769_smartservice";
    private $conn;
    
    public function __construct() {
        $this->connect();
    }
    
    private function connect() {
        try {
            $this->conn = new mysqli($this->host, $this->username, $this->password, $this->dbname);
            
            if ($this->conn->connect_error) {
                die("Connection failed: " . $this->conn->connect_error);
            }
            
            // Set charset to utf8mb4
            $this->conn->set_charset("utf8mb4");
            
        } catch (Exception $e) {
            die("Database connection error: " . $e->getMessage());
        }
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    public function query($sql) {
        return $this->conn->query($sql);
    }
    
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    public function getLastId() {
        return $this->conn->insert_id;
    }
    
    public function close() {
        if ($this->conn) {
            $this->conn->close();
        }
    }
    
    // Prevent SQL injection
    public function prepare($sql) {
        return $this->conn->prepare($sql);
    }
}

// Create database instance
$database = new Database();
$conn = $database->getConnection();

// Helper functions
function clean_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

function redirect($url) {
    header("Location: $url");
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function getUserRole() {
    return isset($_SESSION['user_role']) ? $_SESSION['user_role'] : null;
}

function getUserId() {
    return isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('../auth/login.php');
    }
}

function requireRole($role) {
    requireLogin();
    if (getUserRole() !== $role) {
        redirect('../auth/login.php');
    }
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
