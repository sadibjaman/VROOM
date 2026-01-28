<?php
// File: passenger/ratings.php
include '../shared/header.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit(); }
$database = new Database();
$conn = $database->getConnection();

// Submit rating
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rideId = (int)$_POST['rideID'];
    $value = (float)$_POST['ratingValue'];
    $comment = trim($_POST['comment'] ?? '');
    
    // Check if ride exists and belongs to current user
    $stmt = $conn->prepare('SELECT riderID FROM rides WHERE rideID = ? AND passengerID = ? AND status = "completed"');
    $stmt->execute([$rideId, $_SESSION['user_id']]);
    if ($row = $stmt->fetch()) {
        $to = (int)$row['riderID'];
        
        // Check if rating already exists for this ride
        $checkStmt = $conn->prepare('SELECT 1 FROM ratings WHERE rideID = ? AND ratedBy = ?');
        $checkStmt->execute([$rideId, $_SESSION['user_id']]);
        
        if ($checkStmt->fetch()) {
            $error = 'You have already rated this ride.';
        } else {
            try {
                $conn->prepare('INSERT INTO ratings (rideID, ratedBy, ratedTo, ratingValue, comment) VALUES (?, ?, ?, ?, ?)')->execute([$rideId, $_SESSION['user_id'], $to, $value, $comment]);
                $success = 'Thanks for your rating!';
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = 'You have already rated this ride.';
                } else {
                    $error = 'An error occurred while submitting your rating.';
                }
            }
        }
    } else {
        $error = 'Invalid ride to rate.';
    }
}

// List completed rides to rate (excluding already rated rides)
$stmt = $conn->prepare('SELECT r.rideID, r.dateTime, u.name as rider_name FROM rides r JOIN users u ON r.riderID = u.userID WHERE r.passengerID = ? AND r.status = "completed" AND NOT EXISTS (SELECT 1 FROM ratings rt WHERE rt.rideID = r.rideID AND rt.ratedBy = ?) ORDER BY r.dateTime DESC LIMIT 20');
$stmt->execute([$_SESSION['user_id'], $_SESSION['user_id']]);
$rides = $stmt->fetchAll();

?>

<div class="container">
    <h1>Ratings</h1>
    <?php if (!empty($success)): ?><div class="alert success"><?php echo $success; ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>

    <div class="form-container">
        <h3>Rate a Completed Ride</h3>
        <?php if (empty($rides)): ?>
            <div class="alert info">
                <h4>All Rides Already Rated</h4>
                <p>You have already rated all your completed rides. You can only rate each ride once.</p>
                <p>When you complete new rides, they will appear here for rating.</p>
            </div>
        <?php else: ?>
        <form method="POST">
            <div class="form-group">
                <label for="rideID">Ride</label>
                <select id="rideID" name="rideID" required>
                    <?php foreach ($rides as $r): ?>
                        <option value="<?php echo (int)$r['rideID']; ?>"><?php echo htmlspecialchars($r['dateTime'] . ' - ' . $r['rider_name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="ratingValue">Rating (1.0 - 5.0)</label>
                <input type="number" step="1" min="1" max="5" id="ratingValue" name="ratingValue" required>
            </div>
            <div class="form-group">
                <label for="comment">Comment</label>
                <textarea id="comment" name="comment" rows="3"></textarea>
            </div>
            <button class="btn" type="submit">Submit Rating</button>
        </form>
        <?php endif; ?>
    </div>

    <p><a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a></p>
</div>

<?php include '../shared/footer.php'; ?>
