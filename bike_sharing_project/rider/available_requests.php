<?php
// File: rider/available_requests.php
include '../shared/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get all available ride requests for this rider (excluding own requests)
$query = "SELECT r.*, rt.origin, rt.destination, rt.estimatedFare, u.name as passenger_name, u.phone as passenger_phone
          FROM rides r 
          JOIN routes rt ON r.routeID = rt.routeID 
          JOIN users u ON r.passengerID = u.userID 
          WHERE r.riderID = ? AND r.status IN ('requested', 'accepted', 'in_progress') AND r.passengerID != ?
          ORDER BY r.status, r.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$requests = $stmt->fetchAll();
?>

<div class="container">
    <h1>Ride Requests</h1>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Date/Time</th>
                    <th>Passenger</th>
                    <th>Contact</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Estimated Fare</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($requests as $request): ?>
                <tr>
                    <td><?php echo date('M j, Y g:i A', strtotime($request['dateTime'])); ?></td>
                    <td><?php echo htmlspecialchars($request['passenger_name']); ?></td>
                    <td><?php echo htmlspecialchars($request['passenger_phone']); ?></td>
                    <td><?php echo htmlspecialchars($request['origin']); ?></td>
                    <td><?php echo htmlspecialchars($request['destination']); ?></td>
                    <td>$<?php echo number_format($request['estimatedFare'], 2); ?></td>
                    <td><span class="status <?php echo $request['status']; ?>"><?php echo ucfirst($request['status']); ?></span></td>
                    <td>
                        <?php if ($request['status'] == 'requested'): ?>
                            <button onclick="updateRideStatus(<?php echo $request['rideID']; ?>, 'accepted')" class="btn">Accept</button>
                            <button onclick="updateRideStatus(<?php echo $request['rideID']; ?>, 'cancelled')" class="btn" style="background: #dc3545;">Decline</button>
                        <?php elseif ($request['status'] == 'accepted'): ?>
                            <button onclick="updateRideStatus(<?php echo $request['rideID']; ?>, 'in_progress')" class="btn">Start Ride</button>
                        <?php elseif ($request['status'] == 'in_progress'): ?>
                            <button onclick="updateRideStatus(<?php echo $request['rideID']; ?>, 'completed')" class="btn btn-secondary">Complete Ride</button>
                        <?php endif; ?>
                        <a href="../rides/ride_details.php?id=<?php echo $request['rideID']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem;">Details</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>

<?php include '../shared/footer.php'; ?>