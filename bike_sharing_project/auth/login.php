<?php
// File: auth/login.php
include '../shared/header.php';

if (isset($_SESSION['user_id'])) {
    header('Location: ../choose_role.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email && $password) {
        $database = new Database();
        $conn = $database->getConnection();

        if (!$conn) {
            $error = 'Unable to connect to the database. Please try again later.';
        } else {
        $stmt = $conn->prepare('SELECT userID, name, email, password FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            header('Location: ../choose_role.php');
            exit();
        } else {
            $error = 'Invalid email or password.';
        }
        }
    } else {
        $error = 'Please enter email and password.';
    }
}
?>

<div class="container">
    <div class="form-container">
        <h2>Login</h2>

        <?php if (!empty($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <button type="submit" class="btn">Login</button>
        </form>

        <p style="text-align:center;margin-top:1rem;">No account? <a href="register.php">Register</a></p>
    </div>
</div>

<?php include '../shared/footer.php'; ?>

