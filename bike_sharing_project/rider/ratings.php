
<?php
// File: rider/ratings.php
include '../shared/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

// Get ratings received by this rider
$query = "SELECT rt.*, r.dateTime, ro.origin, ro.destination, u.name as passenger_name
          FROM ratings rt
          JOIN rides r ON rt.rideID = r.rideID
          JOIN routes ro ON r.routeID = ro.routeID
          JOIN users u ON rt.ratedBy = u.userID
          WHERE rt.ratedTo = ?
          ORDER BY rt.ratingDate DESC";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$ratings = $stmt->fetchAll();

// Get rating statistics
$query = "SELECT 
            COUNT(*) as total_ratings,
            AVG(ratingValue) as avg_rating,
            SUM(CASE WHEN ratingValue = 5 THEN 1 ELSE 0 END) as five_star,
            SUM(CASE WHEN ratingValue = 4 THEN 1 ELSE 0 END) as four_star,
            SUM(CASE WHEN ratingValue = 3 THEN 1 ELSE 0 END) as three_star,
            SUM(CASE WHEN ratingValue = 2 THEN 1 ELSE 0 END) as two_star,
            SUM(CASE WHEN ratingValue = 1 THEN 1 ELSE 0 END) as one_star
          FROM ratings WHERE ratedTo = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$stats = $stmt->fetch();
?>

<div class="container">
    <h1>My Ratings</h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <div class="card-icon">⭐</div>
            <h3>Average Rating</h3>
            <p style="font-size: 2rem; color: #ffd700;"><?php echo number_format($stats['avg_rating'] ?: 0, 1); ?></p>
            <p><?php echo $stats['total_ratings']; ?> reviews</p>
        </div>
        
        <div class="dashboard-card">
            <h3>Rating Breakdown</h3>
            <p>5⭐: <?php echo $stats['five_star']; ?></p>
            <p>4⭐: <?php echo $stats['four_star']; ?></p>
            <p>3⭐: <?php echo $stats['three_star']; ?></p>
            <p>2⭐: <?php echo $stats['two_star']; ?></p>
            <p>1⭐: <?php echo $stats['one_star']; ?></p>
        </div>
    </div>
    
    <div class="table-container">
        <h3>Recent Reviews</h3>
        <table>
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Passenger</th>
                    <th>Route</th>
                    <th>Rating</th>
                    <th>Comment</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ratings as $rating): ?>
                <tr>
                    <td><?php echo date('M j, Y', strtotime($rating['ratingDate'])); ?></td>
                    <td><?php echo htmlspecialchars($rating['passenger_name']); ?></td>
                    <td><?php echo htmlspecialchars($rating['origin'] . ' → ' . $rating['destination']); ?></td>
                    <td><span class="stars"><?php echo str_repeat('⭐', $rating['ratingValue']); ?></span></td>
                    <td><?php echo htmlspecialchars($rating['comment']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <a href="dashboard.php" class="btn">Back to Dashboard</a>
</div>

<?php include '../shared/footer.php'; ?>