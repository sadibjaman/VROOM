<?php
// File: rides/route_suggestions.php
include '../shared/header.php';

// Optional auth for suggestions page
// if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit(); }

$database = new Database();
$conn = $database->getConnection();

$origin = trim($_GET['origin'] ?? '');
$destination = trim($_GET['destination'] ?? '');

$suggested_routes = [];
$mock_steps = [];
if ($origin !== '' || $destination !== '') {
    $stmt = $conn->prepare(
        "SELECT DISTINCT origin, destination, estimatedTime, estimatedDistance, estimatedFare
         FROM routes
         WHERE origin LIKE ? OR destination LIKE ?
         ORDER BY estimatedFare ASC
         LIMIT 10"
    );
    $stmt->execute(["%$origin%", "%$destination%"]);
    $suggested_routes = $stmt->fetchAll();

    // Compute fallback fare if missing
    foreach ($suggested_routes as &$route) {
        if (!isset($route['estimatedFare']) || $route['estimatedFare'] === null || $route['estimatedFare'] === '') {
            $distance = isset($route['estimatedDistance']) ? (float)$route['estimatedDistance'] : 0.0;
            $time = isset($route['estimatedTime']) ? (int)$route['estimatedTime'] : 0;
            $baseRatePerKm = 2.0; // fallback base rate
            $fare = ($distance * $baseRatePerKm) + ($time * 0.1);
            $route['estimatedFare'] = round($fare, 2);
        }
    }
    unset($route);

    // Build mock turn-by-turn steps with randomness
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
    $num_steps = rand(4, 7);
    for ($i = 0; $i < $num_steps; $i++) {
        $tpl = $directions_templates[array_rand($directions_templates)];
        if (strpos($tpl, '%d') !== false && strpos($tpl, '%s') !== false) {
            $mock_steps[] = sprintf($tpl, rand(30, 300), $ordinal[array_rand($ordinal)]);
        } elseif (strpos($tpl, '%d') !== false) {
            $mock_steps[] = sprintf($tpl, rand(30, 400));
        } elseif (strpos($tpl, '%s') !== false) {
            $mock_steps[] = sprintf($tpl, $sides[array_rand($sides)]);
        } else {
            $mock_steps[] = $tpl;
        }
    }
}

// Popular routes
$stmt = $conn->query(
    "SELECT rt.origin, rt.destination, COUNT(*) as ride_count, AVG(rt.estimatedFare) as avg_fare
     FROM routes rt
     JOIN rides r ON rt.routeID = r.routeID
     GROUP BY rt.origin, rt.destination
     ORDER BY ride_count DESC
     LIMIT 5"
);
$popular_routes = $stmt->fetchAll();
?>

<div class="container route-suggestions">
    <h1>Route Suggestions</h1>
    
    <div class="form-container">
        <h3>Find Routes</h3>
        <form method="GET">
            <div class="form-group">
                <label for="origin">From:</label>
                <input type="text" id="origin" name="origin" value="<?php echo htmlspecialchars($origin); ?>" placeholder="Enter pickup location">
            </div>
            <div class="form-group">
                <label for="destination">To:</label>
                <input type="text" id="destination" name="destination" value="<?php echo htmlspecialchars($destination); ?>" placeholder="Enter destination">
            </div>
            <button type="submit" class="btn">Find Routes</button>
        </form>
    </div>
    
    <?php if (!empty($suggested_routes)): ?>
    <div class="table-container">
        <h3>Suggested Routes</h3>
        <table>
            <thead>
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Est. Time</th>
                    <th>Est. Distance</th>
                    <th>Est. Fare</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($suggested_routes as $route): ?>
                <tr>
                    <td><?php echo htmlspecialchars($route['origin']); ?></td>
                    <td><?php echo htmlspecialchars($route['destination']); ?></td>
                    <td><?php echo (int)$route['estimatedTime']; ?> min</td>
                    <td><?php echo number_format($route['estimatedDistance'], 1); ?> km</td>
                    <td>$<?php echo number_format((float)$route['estimatedFare'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

    <div class="table-container">
        <h3>Popular Routes</h3>
        <table>
            <thead>
                <tr>
                    <th>From</th>
                    <th>To</th>
                    <th>Rides</th>
                    <th>Avg. Fare</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($popular_routes as $route): ?>
                <tr>
                    <td><?php echo htmlspecialchars($route['origin']); ?></td>
                    <td><?php echo htmlspecialchars($route['destination']); ?></td>
                    <td><?php echo (int)$route['ride_count']; ?></td>
                    <td>$<?php echo number_format($route['avg_fare'], 2); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <a href="/bike_sharing_project/" class="btn">Back to Home</a>
</div>

<?php include '../shared/footer.php'; ?>