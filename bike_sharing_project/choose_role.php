<?php
// File: choose_role.php
include 'shared/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Check if user is already a passenger
$query = "SELECT * FROM passengers WHERE passengerID = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$is_passenger = $stmt->fetch();

// Check if user is already a rider
$query = "SELECT * FROM riders WHERE riderID = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$is_rider = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['become_passenger']) && !$is_passenger) {
        $query = "INSERT INTO passengers (passengerID) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $is_passenger = true;
    }
    
    if (isset($_POST['become_rider']) && !$is_rider) {
        $query = "INSERT INTO riders (riderID) VALUES (?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['user_id']]);
        $is_rider = true;
    }
    
    if (isset($_POST['role'])) {
        if ($_POST['role'] == 'passenger') {
            header("Location: passenger/dashboard.php");
        } else {
            header("Location: rider/dashboard.php");
        }
        exit();
    }
}
?>

<div class="container">
    <div class="form-container">
        <h2><center>Choose Your Role</center></h2>
        
        
        <form method="POST">
            <div class="dashboard-grid" style="margin: 2rem 0;">
                <?php if ($is_passenger): ?>
                    <div class="dashboard-card">
                        <div class="card-icon">👤</div>
                        <h3>Passenger</h3>
                        <p>Book rides and travel safely</p>
                        <button type="submit" name="role" value="passenger" class="btn">Go to Passenger Dashboard</button>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-icon">👤</div>
                        <h3>Become a Passenger</h3>
                        <p>Book rides and travel safely</p>
                        <button type="submit" name="become_passenger" class="btn btn-secondary">Become Passenger</button>
                    </div>
                <?php endif; ?>
                
                <?php if ($is_rider): ?>
                    <div class="dashboard-card">
                        <div class="card-icon">🏍️</div>
                        <h3>Rider</h3>
                        <p>Provide rides and earn money</p>
                        <button type="submit" name="role" value="rider" class="btn">Go to Rider Dashboard</button>
                    </div>
                <?php else: ?>
                    <div class="dashboard-card">
                        <div class="card-icon">🏍️</div>
                        <h3>Become a Rider</h3>
                        <p>Provide rides and earn money</p>
                        <button type="submit" name="become_rider" class="btn btn-secondary">Become Rider</button>
                    </div>
                <?php endif; ?>
            </div>
        </form>
    </div>
    
</div>

<?php include 'shared/footer.php'; ?>

