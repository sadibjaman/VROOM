<?php
// File: passenger/book_ride.php
include '../shared/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../auth/login.php");
    exit();
}

$database = new Database();
$conn = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $origin = $_POST['origin'];
    $destination = $_POST['destination'];
    $rideType = $_POST['rideType'];
    $dateTime = date('Y-m-d H:i:s'); // Use current datetime
    
    // Create or find route
    $query = "SELECT routeID FROM routes WHERE origin = ? AND destination = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$origin, $destination]);
    $route = $stmt->fetch();
    
    if (!$route) {
        // Create new route with estimated values
        $estimatedTime = rand(15, 60); // Mock estimation
        $estimatedDistance = rand(5, 25); // Mock estimation
        $baseRate = $rideType == 'motorbike' ? 2.5 : ($rideType == 'scooter' ? 2.0 : 1.5);
        $estimatedFare = $estimatedDistance * $baseRate;
        
        $query = "INSERT INTO routes (origin, destination, estimatedTime, estimatedDistance, estimatedFare) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->execute([$origin, $destination, $estimatedTime, $estimatedDistance, $estimatedFare]);
        $routeID = $conn->lastInsertId();
    } else {
        $routeID = $route['routeID'];
    }
    
    // Find available rider with specified vehicle type (excluding current user's own vehicles)
    $query = "SELECT r.riderID, v.vehicleID FROM riders r 
              JOIN vehicles v ON r.riderID = v.riderID 
              WHERE v.status = 'available' AND v.rideType = ? AND r.riderID != ?
              ORDER BY RAND() LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->execute([$rideType, $_SESSION['user_id']]);
    $rider = $stmt->fetch();
    
    if ($rider) {
        // Create ride request
        $query = "INSERT INTO rides (passengerID, riderID, vehicleID, routeID, dateTime, status) VALUES (?, ?, ?, ?, ?, 'requested')";
        $stmt = $conn->prepare($query);
        $stmt->execute([$_SESSION['user_id'], $rider['riderID'], $rider['vehicleID'], $routeID, $dateTime]);
        
        $success = "Ride request submitted! You'll be notified when a rider accepts.";
    } else {
        $error = "No available riders found for this ride type. Please try again later.";
    }
}
?>

<div class="container">
    <h1>Book a Ride</h1>
    
    <?php if (isset($success)): ?>
        <div class="alert success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert error"><?php echo $error; ?></div>
    <?php endif; ?>
    
    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label for="origin">Pickup Location:</label>
                <input type="text" id="origin" name="origin" required>
            </div>
            <div class="form-group">
                <label for="destination">Destination:</label>
                <input type="text" id="destination" name="destination" required>
            </div>
            <div class="form-group">
                <label for="rideType">Ride Type:</label>
                <select id="rideType" name="rideType" required>
                    <?php $pref = isset($_GET['rideType']) ? strtolower($_GET['rideType']) : ''; ?>
                    <option value="motorbike" <?php echo $pref==='motorbike' ? 'selected' : ''; ?>>Motorbike</option>
                    <option value="scooter" <?php echo $pref==='scooter' ? 'selected' : ''; ?>>Scooter</option>
                    <option value="ebike" <?php echo $pref==='ebike' ? 'selected' : ''; ?>>E-Bike</option>
                </select>
            </div>
            <div id="fareEstimate" class="alert info" style="display:none;"></div>
            <div class="form-group">
                <a id="routeSuggestionsLink" href="/bike_sharing_project/rides/route_suggestions.php" class="btn btn-secondary" target="_blank" style="display:none;">See route suggestions</a>
            </div>
            <button type="submit" class="btn">Request Ride</button>