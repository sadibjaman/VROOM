
<?php
// File: passenger/dashboard.php
include '../shared/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Check if user is a passenger
$query = "SELECT * FROM passengers WHERE passengerID = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$passenger = $stmt->fetch();

if (!$passenger) {
    header("Location: ../choose_role.php");
    exit();
}

// Get recent rides
$query = "SELECT r.*, rt.origin, rt.destination, u.name as rider_name 
          FROM rides r 
          JOIN routes rt ON r.routeID = rt.routeID 
          LEFT JOIN users u ON r.riderID = u.userID 
          WHERE r.passengerID = ? 
          ORDER BY r.dateTime DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$recent_rides = $stmt->fetchAll();

// Get user wallet balance
$query = "SELECT walletBalance FROM users WHERE userID = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();
?>

<div class="container">
    <h1><center>Passenger Dashboard</center></h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon">🚗</div>
            <h3>Book a Ride</h3>
            <p>Find a ride to your destination</p>
            <a href="book_ride.php" class="btn">Book Now</a>
        </div>
        <div class="dashboard-card">
            <div class="card-icon">🧭</div>
            <h3>Available Rides</h3>
            <p>See riders currently available</p>
            <a href="available_rides.php" class="btn btn-secondary">Browse</a>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">📊</div>
            <h3>Total Rides</h3>
            <p><?php echo $passenger['totalRides']; ?> completed rides</p>
        </div>
        
        <div class="dashboard-card">
            <div class="card-icon">💰</div>
            <h3>Wallet Balance</h3>
            <p>$<?php echo number_format($user['walletBalance'], 2); ?></p>
            <a href="payments.php" class="btn btn-secondary">Add Funds</a>
        </div>
    </div>
    
    <div class="table-container">
        <h3><center>Recent Rides</center></h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>From</th>
                    <th>To</th>
                    <th>Rider</th>
                    <th>Fare</th>
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
                    <td><?php echo htmlspecialchars($ride['rider_name'] ?? 'TBD'); ?></td>
                    <td>$<?php echo number_format($ride['actualFare'], 2); ?></td>
                    <td><span class="status <?php echo $ride['status']; ?>"><?php echo ucfirst($ride['status']); ?></span></td>
                    <td>
                        <a href="../rides/ride_details.php?id=<?php echo $ride['rideID']; ?>" class="btn btn-secondary" style="padding: 5px 10px; font-size: 0.8rem; margin-right: 5px;">View</a>
                        <?php if ($ride['status'] == 'completed'): ?>
                            <a href="ratings.php?ride=<?php echo $ride['rideID']; ?>" class="btn" style="padding: 5px 10px; font-size: 0.8rem;">Rate</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <?php
    // Quick rating prompt for the most recent completed ride the passenger hasn't rated yet
    $q = $conn->prepare('SELECT r.rideID, u.name AS rider_name FROM rides r JOIN users u ON r.riderID = u.userID WHERE r.passengerID = ? AND r.status = "completed" AND NOT EXISTS (SELECT 1 FROM ratings rt WHERE rt.rideID = r.rideID AND rt.ratedBy = ?) ORDER BY r.dateTime DESC LIMIT 1');
    $q->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
    $to_rate = $q->fetch();
    if ($to_rate): ?>
    <div class="form-container">
        <h3>Rate your recent ride with <?php echo htmlspecialchars($to_rate['rider_name']); ?></h3>
        <form method="POST" action="ratings.php">
            <input type="hidden" name="rideID" value="<?php echo (int)$to_rate['rideID']; ?>">
            <div class="form-group">
                <label for="ratingValue">Rating (1.0 - 5.0)</label>
                <input type="number" step="0.1" min="1" max="5" id="ratingValue" name="ratingValue" required>
            </div>
            <div class="form-group">
                <label for="comment">Comment</label>
                <textarea id="comment" name="comment" rows="2" placeholder="Optional"></textarea>
            </div>
            <button class="btn" type="submit">Submit Rating</button>
            <a class="btn btn-secondary" href="ratings.php?ride=<?php echo (int)$to_rate['rideID']; ?>">Open Ratings Page</a>
        </form>
    </div>
    <?php endif; ?>

    <p><a href="ride_history.php" class="btn btn-secondary">View All Rides</a> <a href="../choose_role.php" class="btn">Switch Role</a></p>
</div>

<?php include '../shared/footer.php'; ?>