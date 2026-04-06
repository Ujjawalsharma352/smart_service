<?php
require_once 'config/db.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle language change
if (isset($_REQUEST['lang'])) {
    $language = $_REQUEST['lang'];
    
    // Validate language
    $validLanguages = ['en', 'hi', 'fr'];
    if (in_array($language, $validLanguages)) {
        $_SESSION['lang'] = $language;
        
        // Update user preference if logged in
        if (function_exists('isLoggedIn') && isLoggedIn()) {
            $userId = getUserId();
            $stmt = $conn->prepare("UPDATE users SET language = ? WHERE id = ?");
            $stmt->bind_param("si", $language, $userId);
            $stmt->execute();
        }
    }
    
    // Redirect back to referring page or dashboard
    $redirectUrl = $_SERVER['HTTP_REFERER'] ?? 'user/dashboard.php';
    header("Location: $redirectUrl");
    exit();
}

// Get current language
function getCurrentLanguage() {
    return $_SESSION['lang'] ?? 'en';
}

// Load language file
function loadLanguage($lang = null) {
    $lang = $lang ?: getCurrentLanguage();
    $langFile = "lang/$lang.php";
    
    if (file_exists($langFile)) {
        require_once $langFile;
    } else {
        require_once 'lang/en.php';
    }
    
    return $GLOBALS['lang'];
}

// Get available languages
function getAvailableLanguages() {
    return [
        'en' => ['name' => 'English', 'flag' => '🇺🇸', 'code' => 'en'],
        'hi' => ['name' => 'हिन्दी', 'flag' => '🇮🇳', 'code' => 'hi'],
        'fr' => ['name' => 'Français', 'flag' => '🇫🇷', 'code' => 'fr']
    ];
}
?>
