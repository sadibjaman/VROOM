<?php
// File: rider/earnings.php
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

// Get earnings summary
$query = "SELECT 
            COUNT(*) as total_rides,
            SUM(actualFare) as total_earnings,
            AVG(actualFare) as avg_fare,
            SUM(CASE WHEN MONTH(dateTime) = MONTH(CURRENT_DATE()) AND YEAR(dateTime) = YEAR(CURRENT_DATE()) THEN actualFare ELSE 0 END) as monthly_earnings
          FROM rides 
          WHERE riderID = ? AND status = 'completed'";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$earnings = $stmt->fetch();

// Get recent completed rides
$query = "SELECT r.*, rt.origin, rt.destination, u.name as passenger_name
          FROM rides r 
          JOIN routes rt ON r.routeID = rt.routeID 
          JOIN users u ON r.passengerID = u.userID 
          WHERE r.riderID = ? AND r.status = 'completed'
          ORDER BY r.dateTime DESC 
          LIMIT 20";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$completed_rides = $stmt->fetchAll();
?>

<div class="container">
    <h1>Earnings & Ride History</h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon">💰</div>
            <h3>Total Earnings</h3>
            <p style="font-size: 2rem; color: #667eea;">$<?php echo number_format($earnings['total_earnings'] ?: 0, 2); ?></p>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">📊</div>
            <h3>This Month</h3>
            <p style="font-size: 1.5rem; color: #4ecdc4;">$<?php echo number_format($earnings['monthly_earnings'] ?: 0, 2); ?></p>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">🚗</div>
            <h3>Total Rides</h3>
            <p style="font-size: 1.5rem; color: #45b7d1;"><?php echo $earnings['total_rides'] ?: 0; ?></p>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">📈</div>
            <h3>Average Fare</h3>
            <p style="font-size: 1.5rem; color: #96ceb4;">$<?php echo number_format($earnings['avg_fare'] ?: 0, 2); ?></p>
        </div>
    </div>
    
    <div class="table-container">
        <h3>Ride History</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Passenger</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Fare</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($completed_rides as $ride): ?>
                <tr>
                    <td><?php echo date('M j, Y g:i A', strtotime($ride['dateTime'])); ?></td>
                    <td><?php echo htmlspecialchars($ride['passenger_name']); ?></td>
                    <td><?php echo htmlspecialchars($ride['origin']); ?></td>
                    <td><?php echo htmlspecialchars($ride['destination']); ?></td>
                    <td>$<?php echo number_format($ride['actualFare'], 2); ?></td>
                    <td>
                        <a href="../rides/ride_details.php?id=<?php echo $ride['rideID']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">View Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>

<?php include '../shared/footer.php'; ?>