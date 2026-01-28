<?php
// File: index.php
include 'shared/header.php';
?>

<div class="hero">
    <div class="container">
        <h1>Welcome to VROOM</h1>
        <p>Your trusted motorbike ride-sharing platform. Quick, safe, and affordable rides.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="auth/register.php" class="btn">Get Started</a>
            <a href="auth/login.php" class="btn btn-secondary">Login</a>
        <?php else: ?>
            <a href="choose_role.php" class="btn">Go to Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<div class="container">
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon">🏍</div>
            <h3>Fast and Reliable</h3>
            <p>Book a ride in seconds and get moving.</p>
        </div>
        <div class="dashboard-card">
            <div class="card-icon">🛡️</div>
            <h3>Safe Rides</h3>
            <p>Verified riders and transparent ratings.</p>
        </div>
        <div class="dashboard-card">
            <div class="card-icon">💸</div>
            <h3>Affordable</h3>
            <p>Clear pricing and wallet payments.</p>
        </div>
    </div>
</div>

<?php include 'shared/footer.php'; ?>