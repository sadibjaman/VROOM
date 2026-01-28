<?php
// File: rides/ride_details.php
include '../shared/header.php';

$rideID = (int)($_GET['id'] ?? 0);
if ($rideID <= 0) { header('Location: ../index.php'); exit(); }

$database = new Database();
$conn = $database->getConnection();

// Expanded ride details including vehicle and payment
$query = "SELECT r.*, rt.origin, rt.destination, rt.estimatedTime, rt.estimatedDistance, rt.estimatedFare,
          up.name as passenger_name, up.phone as passenger_phone,
          ur.name as rider_name, ur.phone as rider_phone,
          v.rideType, v.model, v.registration,
          p.paymentMethod, p.payStatus, p.amount as payment_amount
          FROM rides r 
          JOIN routes rt ON r.routeID = rt.routeID 
          JOIN users up ON r.passengerID = up.userID 
          JOIN users ur ON r.riderID = ur.userID 
          LEFT JOIN vehicles v ON r.vehicleID = v.vehicleID
          LEFT JOIN payments p ON r.rideID = p.rideID
          WHERE r.rideID = ?";
$stmt = $conn->prepare($query);
$stmt->execute([$rideID]);
$ride = $stmt->fetch();
if (!$ride) { header('Location: ../index.php'); exit(); }

$is_passenger = isset($_SESSION['user_id']) && ($ride['passengerID'] == $_SESSION['user_id']);
$is_rider = isset($_SESSION['user_id']) && ($ride['riderID'] == $_SESSION['user_id']);
?>

<div class="container">
    <h1>Ride Details</h1>
    
    <div class="dashboard-grid">
        <div class="dashboard-card">
            <h3>Ride Information</h3>
            <p><strong>Ride ID:</strong> #<?php echo $ride['rideID']; ?></p>
            <p><strong>Date & Time:</strong> <?php echo htmlspecialchars($ride['dateTime']); ?></p>
            <p><strong>Status:</strong> <span class="status <?php echo $ride['status']; ?>"><?php echo ucfirst($ride['status']); ?></span></p>
            <p><strong>From:</strong> <?php echo htmlspecialchars($ride['origin']); ?></p>
            <p><strong>To:</strong> <?php echo htmlspecialchars($ride['destination']); ?></p>
        </div>
        
        <div class="dashboard-card">
            <h3>Vehicle Details</h3>
            <p><strong>Type:</strong> <?php echo htmlspecialchars($ride['rideType'] ?? ''); ?></p>
            <p><strong>Model:</strong> <?php echo htmlspecialchars($ride['model'] ?? ''); ?></p>
            <p><strong>Registration:</strong> <?php echo htmlspecialchars($ride['registration'] ?? ''); ?></p>
        </div>
        
        <div class="dashboard-card">
            <h3><?php echo $is_passenger ? 'Rider' : 'Passenger'; ?> Details</h3>
            <?php if ($is_passenger): ?>
                <p><strong>Rider Name:</strong> <?php echo htmlspecialchars($ride['rider_name']); ?></p>
                <p><strong>Rider Phone:</strong> <?php echo htmlspecialchars($ride['rider_phone']); ?></p>
            <?php else: ?>
                <p><strong>Passenger Name:</strong> <?php echo htmlspecialchars($ride['passenger_name']); ?></p>
                <p><strong>Passenger Phone:</strong> <?php echo htmlspecialchars($ride['passenger_phone']); ?></p>
            <?php endif; ?>
        </div>
        
        <div class="dashboard-card">
            <h3>Fare & Payment</h3>
            <p><strong>Estimated Fare:</strong> $<?php echo number_format((float)$ride['estimatedFare'], 2); ?></p>
            <p><strong>Actual Fare:</strong> <?php echo $ride['actualFare'] !== null ? '$' . number_format((float)$ride['actualFare'], 2) : '—'; ?></p>
            <?php if (!empty($ride['paymentMethod'])): ?>
                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars(ucfirst($ride['paymentMethod'])); ?></p>
                <p><strong>Payment Status:</strong> <span class="status <?php echo htmlspecialchars($ride['payStatus']); ?>"><?php echo htmlspecialchars(ucfirst($ride['payStatus'])); ?></span></p>
            <?php endif; ?>
        </div>
    </div>

    <?php
    // Compact route suggestion text line
    $directions_templates = [
        'Go straight for %d meters',
        'Turn left at the next intersection',
        'Turn right after %d meters',
        'Keep left to continue for %d meters',
        'At the roundabout, take the %s exit',
        'Make a U-turn when possible',
        'Your destination will be on the %s',
    ];
    $ordinal = ['1st','2nd','3rd','4th'];
    $sides = ['left','right'];
    $num_steps = rand(3, 5);
    $parts = [htmlspecialchars($ride['origin'])];
    for ($i = 0; $i < $num_steps; $i++) {
        $tpl = $directions_templates[array_rand($directions_templates)];
        if (strpos($tpl, '%d') !== false && strpos($tpl, '%s') !== false) {
            $parts[] = sprintf($tpl, rand(30, 300), $ordinal[array_rand($ordinal)]);
        } elseif (strpos($tpl, '%d') !== false) {
            $parts[] = sprintf($tpl, rand(30, 400));
        } elseif (strpos($tpl, '%s') !== false) {
            $parts[] = sprintf($tpl, $sides[array_rand($sides)]);
        } else {
            $parts[] = $tpl;
        }
    }
    $parts[] = htmlspecialchars($ride['destination']);
    $compact_route_line = implode(' --> ', $parts);
    ?>

    <div class="table-container">
        <h3><center>Taken Route based on Suggestion</center></h3>
        <p style="white-space: normal; line-height: 1.6;"><?php echo $compact_route_line; ?></p>
    </div>

    <div class="actions" style="margin:1rem 0;">
        <?php if ($ride['status'] === 'requested' && !$is_passenger): ?>
            <button class="btn" onclick="updateRideStatus(<?php echo (int)$rideID; ?>, 'accepted')">Accept Ride</button>
        <?php endif; ?>
        <?php if ($ride['status'] === 'accepted'): ?>
            <button class="btn btn-secondary" onclick="updateRideStatus(<?php echo (int)$rideID; ?>, 'in_progress')">Start Ride</button>
        <?php endif; ?>
        <?php if ($ride['status'] === 'in_progress'): ?>
            <button class="btn" onclick="updateRideStatus(<?php echo (int)$rideID; ?>, 'completed')">Complete Ride</button>
        <?php endif; ?>
    </div>

    <div id="acceptedRouteInfo" class="table-container" style="display:none;"></div>

    <div style="text-align: center; margin: 2rem 0;">
        <?php if ($is_passenger): ?>
            <a href="../passenger/dashboard.php" class="btn">Back to Passenger Dashboard</a>
            <?php if ($ride['status'] == 'completed'): ?>
                <a href="../passenger/ratings.php?ride=<?php echo (int)$rideID; ?>" class="btn btn-secondary">Rate This Ride</a>
            <?php endif; ?>
        <?php else: ?>
            <a href="../rider/dashboard.php" class="btn">Back to Rider Dashboard</a>
        <?php endif; ?>
    </div>
</div>

<?php include '../shared/footer.php'; ?>

