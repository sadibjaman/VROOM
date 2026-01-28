<?php
// File: passenger/payments.php
include '../shared/header.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit(); }
$database = new Database();
$conn = $database->getConnection();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = (float)($_POST['amount'] ?? 0);
    if ($amount > 0) {
        $conn->prepare('UPDATE users SET walletBalance = walletBalance + ? WHERE userID = ?')->execute([$amount, $_SESSION['user_id']]);
        $message = 'Funds added to wallet.';
    }
}

$stmt = $conn->prepare('SELECT walletBalance FROM users WHERE userID = ?');
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<div class="container">
    <h1>Wallet & Payments</h1>
    <?php if (!empty($message)): ?><div class="alert success"><?php echo $message; ?></div><?php endif; ?>
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Current Balance</h3>
            <p>$<?php echo number_format($user['walletBalance'] ?? 0, 2); ?></p>
        </div>
    </div>
    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label for="amount">Add Funds ($)</label>
                <input type="number" id="amount" name="amount" step="0.01" min="1" required>
            </div>
            <button class="btn" type="submit">Add to Wallet</button>
        </form>
    </div>
    <p><a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a></p>
</div>

<?php include '../shared/footer.php'; ?>

