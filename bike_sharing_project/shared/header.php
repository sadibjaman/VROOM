<?php
// File: shared/header.php
session_start();
require_once __DIR__ . '/../config/database.php';

// Determine page type for background styling
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$page_class = '';

// Set page-specific classes
if (strpos($_SERVER['REQUEST_URI'], '/auth/') !== false) {
    $page_class = 'auth-page';
} elseif (strpos($_SERVER['REQUEST_URI'], '/passenger/dashboard') !== false || 
          strpos($_SERVER['REQUEST_URI'], '/rider/dashboard') !== false) {
    $page_class = 'dashboard-page';
} elseif (strpos($_SERVER['REQUEST_URI'], '/rides/') !== false || 
          strpos($_SERVER['REQUEST_URI'], '/passenger/book_ride') !== false ||
          strpos($_SERVER['REQUEST_URI'], '/passenger/available_rides') !== false ||
          strpos($_SERVER['REQUEST_URI'], '/rider/available_requests') !== false) {
    $page_class = 'ride-page';
} elseif (strpos($_SERVER['REQUEST_URI'], '/ratings') !== false) {
    $page_class = 'rating-page';
} elseif (strpos($_SERVER['REQUEST_URI'], '/payments') !== false) {
    $page_class = 'payment-page';
} elseif ($_SERVER['REQUEST_URI'] === '/bike_sharing_project/' || $_SERVER['REQUEST_URI'] === '/bike_sharing_project/index.php') {
    $page_class = 'home-page';
} elseif (strpos($_SERVER['REQUEST_URI'], '/choose_role') !== false) {
    $page_class = 'role-page';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>VROOM - Motorbike Sharing Platform</title>
    <link rel="stylesheet" href="/bike_sharing_project/assets/css/style.css">
</head>
<body<?php echo $page_class ? ' class="' . $page_class . '"' : ''; ?>>
    <header>
        <nav class="container">
            <ul>
                <li><a href="/bike_sharing_project/" class="logo">🏍 VROOM</a></li>
                <li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <a href="/bike_sharing_project/choose_role.php" class="btn btn-secondary">Dashboard</a>
                        <a href="/bike_sharing_project/auth/logout.php" class="btn">Logout (<?php echo htmlspecialchars($_SESSION['user_name']); ?>)</a>
                    <?php else: ?>
                        <a href="/bike_sharing_project/auth/login.php" class="btn btn-secondary">Login</a>
                        <a href="/bike_sharing_project/auth/register.php" class="btn">Register</a>
                    <?php endif; ?>
                </li>
            </ul>
        </nav>
    </header>
    <main>
