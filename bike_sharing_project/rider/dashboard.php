<?php
// File: rider/dashboard.php
include '../shared/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Ensure user is a rider
$stmt = $conn->prepare('SELECT * FROM riders WHERE riderID = ?');
$stmt->execute([$_SESSION['user_id']]);
$rider = $stmt->fetch();
if (!$rider) {
    header('Location: ../choose_role.php');
    exit();
}

// Recent rides for this rider
$stmt = $conn->prepare("SELECT r.*, rt.origin, rt.destination, u.name as passenger_name
                        FROM rides r
                        JOIN routes rt ON r.routeID = rt.routeID
                        JOIN users u ON r.passengerID = u.userID
                        WHERE r.riderID = ?
                        ORDER BY r.created_at DESC LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_rides = $stmt->fetchAll();

// Get rider statistics
$stmt = $conn->prepare("SELECT 
                        COUNT(*) as total_rides,
                        SUM(actualFare) as total_earnings,
                        AVG(actualFare) as avg_fare
                        FROM rides 
                        WHERE riderID = ? AND status = 'completed'");
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();

// Get average rating
$stmt = $conn->prepare("SELECT 
                        COUNT(*) as total_ratings,
                        AVG(ratingValue) as avg_rating
                        FROM ratings 
                        WHERE ratedTo = ?");
$stmt->execute([$_SESSION['user_id']]);
$rating_stats = $stmt->fetch();
?>

<div class="container">
    <h1>Rider Dashboard</h1>

    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon">📥</div>
            <h3>Available Requests</h3>
            <p>See ride requests you can accept</p>
            <a href="available_requests.php" class="btn">View Requests</a>
        </div>
        <div class="dashboard-card">
            <div class="card-icon">💼</div>
            <h3>Manage Vehicle</h3>
            <p>Add or edit your vehicles</p>
            <a href="manage_vehicle.php" class="btn btn-secondary">Manage</a>
        </div>
        <div class="dashboard-card">
            <div class="card-icon">💰</div>
            <h3>Earnings</h3>
            <p>Total: $<?php echo number_format($stats['total_earnings'] ?: 0, 2); ?></p>
            <p>Rides: <?php echo $stats['total_rides'] ?: 0; ?></p>
            <a href="earnings.php" class="btn btn-secondary">View Details</a>
        </div>
        <div class="dashboard-card">
            <div class="card-icon">⭐</div>
            <h3>Average Rating</h3>
            <p style="font-size: 1.5rem; color: #ffd700;"><?php echo number_format($rating_stats['avg_rating'] ?: 0, 1); ?></p>
            <p><?php echo $rating_stats['total_ratings'] ?: 0; ?> reviews</p>
            <a href="ratings.php" class="btn btn-secondary">View Ratings</a>
        </div>
        <div class="dashboard-card">
            <div class="card-icon">📋</div>
            <h3>Ride History</h3>
            <p>View all your completed rides</p>
            <a href="earnings.php" class="btn btn-secondary">View History</a>
        </div>
    </div>

    <div class="table-container">
        <h3>Recent Rides</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Passenger</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($recent_rides as $ride): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($ride['dateTime'])); ?></td>
                    <td><?php echo htmlspecialchars($ride['origin']); ?></td>
                    <td><?php echo htmlspecialchars($ride['destination']); ?></td>
                    <td><?php echo htmlspecialchars($ride['passenger_name']); ?></td>
                    <td><span class="status <?php echo $ride['status']; ?>"><?php echo ucfirst($ride['status']); ?></span></td>
                    <td>
                        <?php if (in_array($ride['status'], ['accepted','in_progress'], true)): ?>
                            <a class="btn btn-secondary" href="/bike_sharing_project/rides/ride_status.php?id=<?php echo (int)$ride['rideID']; ?>&to=completed">Complete ride</a>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p><a href="ratings.php" class="btn btn-secondary">View Ratings</a> <a href="../choose_role.php" class="btn">Switch Role</a></p>
</div>

<?php include '../shared/footer.php'; ?>

