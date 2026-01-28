<?php
// File: passenger/ride_history.php
include '../shared/header.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit(); }
$database = new Database();
$conn = $database->getConnection();

$stmt = $conn->prepare("SELECT r.*, rt.origin, rt.destination, u.name as rider_name
                        FROM rides r
                        JOIN routes rt ON r.routeID = rt.routeID
                        LEFT JOIN users u ON r.riderID = u.userID
                        WHERE r.passengerID = ? ORDER BY r.dateTime DESC");
$stmt->execute([$_SESSION['user_id']]);
$rides = $stmt->fetchAll();
?>

<div class="container">
    <h1>Your Ride History</h1>
    <div class="table-container">
        <table>
            <thead><tr><th>Date</th><th>From</th><th>To</th><th>Rider</th><th>Status</th><th>Fare</th></tr></thead>
            <tbody>
                <?php foreach ($rides as $ride): ?>
                <tr>
                    <td><?php echo htmlspecialchars($ride['dateTime']); ?></td>
                    <td><?php echo htmlspecialchars($ride['origin']); ?></td>
                    <td><?php echo htmlspecialchars($ride['destination']); ?></td>
                    <td><?php echo htmlspecialchars($ride['rider_name'] ?? 'TBD'); ?></td>
                    <td><span class="status <?php echo $ride['status']; ?>"><?php echo ucfirst($ride['status']); ?></span></td>
                    <td><?php echo $ride['actualFare'] ? '$' . number_format($ride['actualFare'], 2) : '-'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <p><a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a></p>
</div>

<?php include '../shared/footer.php'; ?>

