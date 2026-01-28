<?php
// File: passenger/available_rides.php
include '../shared/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Ensure user is a passenger
$stmt = $conn->prepare('SELECT passengerID FROM passengers WHERE passengerID = ?');
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) {
    header('Location: ../choose_role.php');
    exit();
}

// List riders/vehicles marked available (excluding current user's own vehicles)
$stmt = $conn->prepare("SELECT u.userID as riderID, u.name as rider_name, v.vehicleID, v.rideType, v.model, v.registration
                      FROM users u
                      JOIN riders r ON r.riderID = u.userID
                      JOIN vehicles v ON v.riderID = r.riderID
                      WHERE v.status = 'available' AND u.userID != ?");
$stmt->execute([$_SESSION['user_id']]);
$available = $stmt->fetchAll();
?>

<div class="container">
    <h1>Available Rides (Riders)</h1>

    <?php if (empty($available)): ?>
        <div class="alert info">
            <h4>No Available Rides</h4>
            <p>There are currently no riders available. Please try again later or book a ride directly.</p>
            <p><a href="book_ride.php" class="btn">Book a Ride</a></p>
        </div>
    <?php else: ?>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Rider</th>
                    <th>Vehicle</th>
                    <th>Type</th>
                    <th>Model</th>
                    <th>Registration</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($available as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['rider_name']); ?></td>
                    <td>#<?php echo (int)$row['vehicleID']; ?></td>
                    <td><?php echo htmlspecialchars(ucfirst($row['rideType'])); ?></td>
                    <td><?php echo htmlspecialchars($row['model']); ?></td>
                    <td><?php echo htmlspecialchars($row['registration']); ?></td>
                    <td><a class="btn" href="book_ride.php?rideType=<?php echo urlencode($row['rideType']); ?>">Request</a></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <p><a class="btn btn-secondary" href="dashboard.php">Back to Dashboard</a></p>
</div>

<?php include '../shared/footer.php'; ?>


