<?php
// File: auth/register.php
include '../shared/header.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $database = new Database();
    $conn = $database->getConnection();
    
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    
    try {
        $query = "INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$name, $email, $phone, $password]);
        
        $user_id = $conn->lastInsertId();
        $_SESSION['user_id'] = $user_id;
        $_SESSION['user_name'] = $name;
        $_SESSION['user_email'] = $email;
        
        header("Location: /bike_sharing_project/choose_role.php");
        exit();
    } catch(PDOException $e) {
        $error = "Registration failed. Email might already exist.";
    }
}
?>

<div class="container">
    <div class="form-container">
        <h2>Create Account</h2>
        
        <?php if (isset($error)): ?>
            <div class="alert error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="name">Full Name:</label>
                <input type="text" id="name" name="name" required>
            </div>
            
            <div class="form-group">
                <label for="email">Email:</label>
                <input type="email" id="email" name="email" required>
            </div>
            
            <div class="form-group">
                <label for="phone">Phone:</label>
                <input type="tel" id="phone" name="phone" required>
            </div>
            
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required minlength="6">
            </div>
            
            <button type="submit" class="btn">Register</button>
        </form>
        
        <p style="text-align: center; margin-top: 1rem;">
            Already have an account? <a href="login.php">Login here</a>
        </p>
    </div>
</div>

<?php include '../shared/footer.php'; ?>