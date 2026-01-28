<?php
// File: rides/ride_status.php

// This endpoint supports:
// - POST JSON: update status (returns JSON)
// - GET with id & to: update and redirect (for demo/testing)

session_start();
require_once '../config/database.php';

$database = new Database();
$conn = $database->getConnection();

// Handle POST JSON API
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id'])) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }

    $input = json_decode(file_get_contents('php://input'), true) ?: [];
    $rideId = isset($input['rideId']) ? (int)$input['rideId'] : 0;
    $status = $input['status'] ?? '';

    if (!$rideId || !in_array($status, ['requested','accepted','in_progress','completed','cancelled'], true)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
        exit();
    }

    try {
        // Permission check: rider or passenger on this ride
        $stmt = $conn->prepare('SELECT * FROM rides WHERE rideID = ? AND (riderID = ? OR passengerID = ?)');
        $stmt->execute([$rideId, $_SESSION['user_id'], $_SESSION['user_id']]);
        $ride = $stmt->fetch();
        if (!$ride) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Permission denied']);
            exit();
        }

        // Update status
        $stmt = $conn->prepare('UPDATE rides SET status = ? WHERE rideID = ?');
        $stmt->execute([$status, $rideId]);

        // Update vehicle status based on ride status
        if ($status === 'accepted') {
            // Mark vehicle as in use when ride is accepted
            $conn->prepare('UPDATE vehicles SET status = "in_use" WHERE vehicleID = ?')
                 ->execute([$ride['vehicleID']]);
        } elseif ($status === 'cancelled') {
            // Mark vehicle as available if ride is cancelled
            $conn->prepare('UPDATE vehicles SET status = "available" WHERE vehicleID = ?')
                 ->execute([$ride['vehicleID']]);
        }

        // If accepted, return route details and a suggested route (mock steps)
        if ($status === 'accepted') {
            $stmt = $conn->prepare('SELECT rt.origin, rt.destination, rt.estimatedTime, rt.estimatedDistance, rt.estimatedFare FROM rides r JOIN routes rt ON r.routeID = rt.routeID WHERE r.rideID = ?');
            $stmt->execute([$rideId]);
            $route = $stmt->fetch() ?: [];

            // Build mock turn-by-turn steps similar to route_suggestions.php
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
            $steps = [];
            for ($i = 0; $i < $num_steps; $i++) {
                $tpl = $directions_templates[array_rand($directions_templates)];
                if (strpos($tpl, '%d') !== false && strpos($tpl, '%s') !== false) {
                    $steps[] = sprintf($tpl, rand(30, 300), $ordinal[array_rand($ordinal)]);
                } elseif (strpos($tpl, '%d') !== false) {
                    $steps[] = sprintf($tpl, rand(30, 400));
                } elseif (strpos($tpl, '%s') !== false) {
                    $steps[] = sprintf($tpl, $sides[array_rand($sides)]);
                } else {
                    $steps[] = $tpl;
                }
            }

            echo json_encode([
                'success' => true,
                'message' => 'Ride accepted',
                'route' => [
                    'origin' => $route['origin'] ?? '',
                    'destination' => $route['destination'] ?? '',
                    'estimatedTime' => isset($route['estimatedTime']) ? (int)$route['estimatedTime'] : 0,
                    'estimatedDistance' => isset($route['estimatedDistance']) ? (float)$route['estimatedDistance'] : 0.0,
                    'estimatedFare' => isset($route['estimatedFare']) ? (float)$route['estimatedFare'] : 0.0,
                ],
                'suggestedSteps' => $steps,
            ]);
            exit(); 
        }

        if ($status === 'completed') {
            // Derive fare from route estimate and settle wallets with small random adjustment
            $stmt = $conn->prepare('SELECT rt.estimatedFare FROM rides r JOIN routes rt ON r.routeID = rt.routeID WHERE r.rideID = ?');
            $stmt->execute([$rideId]);
            $route = $stmt->fetch();
            $baseFare = (float)($route['estimatedFare'] ?? 0);

            // Random small adjustment between -3% and +3%
            $percent = (mt_rand(-30, 30)) / 1000.0; // -0.03 .. +0.03
            $adjustedFare = round($baseFare * (1 + $percent), 2);

            // Set actual fare
            $conn->prepare('UPDATE rides SET actualFare = ? WHERE rideID = ?')->execute([$adjustedFare, $rideId]);

            // Payment record
            $conn->prepare("INSERT INTO payments (rideID, paymentMethod, amount, payStatus) VALUES (?, 'wallet', ?, 'completed')")
                 ->execute([$rideId, $adjustedFare]);

            // Increment passenger total rides
            $conn->prepare('UPDATE passengers SET totalRides = totalRides + 1 WHERE passengerID = ?')
                 ->execute([$ride['passengerID']]);

            // Wallet movements: charge passenger, credit rider 80%
            $conn->prepare('UPDATE users SET walletBalance = walletBalance - ? WHERE userID = ?')
                 ->execute([$adjustedFare, $ride['passengerID']]);
            $conn->prepare('UPDATE users SET walletBalance = walletBalance + ? WHERE userID = ?')
                 ->execute([$adjustedFare * 0.8, $ride['riderID']]);

            // Update vehicle status back to available
            $conn->prepare('UPDATE vehicles SET status = "available" WHERE vehicleID = ?')
                 ->execute([$ride['vehicleID']]);

            echo json_encode([
                'success' => true,
                'message' => 'Ride completed',
                'fare' => [
                    'base' => $baseFare,
                    'adjusted' => $adjustedFare,
                    'percent' => $percent,
                ],
            ]);
            exit();
        }

        echo json_encode(['success' => true, 'message' => 'Status updated successfully']);
    } catch (Throwable $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Server error']);
    }
    exit();
}

// Handle GET for demo redirect flow
$rideId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$to = $_GET['to'] ?? '';
if ($rideId > 0 && in_array($to, ['accepted','in_progress','completed','cancelled'], true)) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../auth/login.php');
        exit();
    }
    // Optional: permission check could be added here as well
    $stmt = $conn->prepare('UPDATE rides SET status = ? WHERE rideID = ?');
    $stmt->execute([$to, $rideId]);

    // Get ride details for vehicle status update
    $stmt = $conn->prepare('SELECT vehicleID FROM rides WHERE rideID = ?');
    $stmt->execute([$rideId]);
    $ride = $stmt->fetch();

    // Update vehicle status based on ride status
    if ($to === 'accepted' && $ride) {
        // Mark vehicle as in use when ride is accepted
        $conn->prepare('UPDATE vehicles SET status = "in_use" WHERE vehicleID = ?')
             ->execute([$ride['vehicleID']]);
    } elseif ($to === 'cancelled' && $ride) {
        // Mark vehicle as available if ride is cancelled
        $conn->prepare('UPDATE vehicles SET status = "available" WHERE vehicleID = ?')
             ->execute([$ride['vehicleID']]);
    }

    // If completing via GET, also settle fare and wallets (mirror POST logic)
    if ($to === 'completed') {
        try {
            // Get route fare and ride participants
            $stmt = $conn->prepare('SELECT r.passengerID, r.riderID, rt.estimatedFare FROM rides r JOIN routes rt ON r.routeID = rt.routeID WHERE r.rideID = ?');
            $stmt->execute([$rideId]);
            $row = $stmt->fetch();
            $baseFare = (float)($row['estimatedFare'] ?? 0);

            $percent = (mt_rand(-30, 30)) / 1000.0; // -0.03 .. +0.03
            $adjustedFare = round($baseFare * (1 + $percent), 2);

            // Set actual fare
            $conn->prepare('UPDATE rides SET actualFare = ? WHERE rideID = ?')->execute([$adjustedFare, $rideId]);

            // Payment record
            $conn->prepare("INSERT INTO payments (rideID, paymentMethod, amount, payStatus) VALUES (?, 'wallet', ?, 'completed')")
                 ->execute([$rideId, $adjustedFare]);

            // Increment passenger total rides
            if (!empty($row['passengerID'])) {
                $conn->prepare('UPDATE passengers SET totalRides = totalRides + 1 WHERE passengerID = ?')
                     ->execute([(int)$row['passengerID']]);
            }

            // Wallet movements: charge passenger, credit rider 80%
            if (!empty($row['passengerID'])) {
                $conn->prepare('UPDATE users SET walletBalance = walletBalance - ? WHERE userID = ?')
                     ->execute([$adjustedFare, (int)$row['passengerID']]);
            }
            if (!empty($row['riderID'])) {
                $conn->prepare('UPDATE users SET walletBalance = walletBalance + ? WHERE userID = ?')
                     ->execute([$adjustedFare * 0.8, (int)$row['riderID']]);
            }

            // Update vehicle status back to available when ride is completed
            if ($ride) {
                $conn->prepare('UPDATE vehicles SET status = "available" WHERE vehicleID = ?')
                     ->execute([$ride['vehicleID']]);
            }
        } catch (Throwable $e) {
            // Ignore here and proceed with redirect to details; errors would be visible in logs
        }
    }
    header('Location: ride_details.php?id=' . $rideId);
    exit();
}

header('Location: ../index.php');
exit();