<?php
// File: rider/manage_vehicle.php
include '../shared/header.php';
if (!isset($_SESSION['user_id'])) { header('Location: ../auth/login.php'); exit(); }

$database = new Database();
$conn = $database->getConnection();

// Ensure rider
$stmt = $conn->prepare('SELECT riderID FROM riders WHERE riderID = ?');
$stmt->execute([$_SESSION['user_id']]);
if (!$stmt->fetch()) { header('Location: ../choose_role.php'); exit(); }

// Handle add vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rideType = $_POST['rideType'];
    $model = $_POST['model'];
    $registration = $_POST['registration'];
    $stmt = $conn->prepare('INSERT INTO vehicles (riderID, rideType, model, registration) VALUES (?, ?, ?, ?)');
    try { $stmt->execute([$_SESSION['user_id'], $rideType, $model, $registration]); $success = 'Vehicle added.'; } catch (Exception $e) { $error = 'Registration already exists.'; }
}

// Fetch vehicles
$stmt = $conn->prepare('SELECT * FROM vehicles WHERE riderID = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$vehicles = $stmt->fetchAll();
?>

<div class="container">
    <h1>Manage Vehicle</h1>
    <?php if (!empty($success)): ?><div class="alert success"><?php echo $success; ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert error"><?php echo $error; ?></div><?php endif; ?>

    <div class="form-container">
        <form method="POST">
            <div class="form-group">
                <label for="rideType">Ride Type</label>
                <select id="rideType" name="rideType" required>
                    <option value="motorbike">Motorbike</option>
                    <option value="scooter">Scooter</option>
                    <option value="ebike">E-Bike</option>
                </select>
            </div>
            <div class="form-group">
                <label for="model">Model</label>
                <input id="model" name="model" required>
            </div>
            <div class="form-group">
                <label for="registration">Registration</label>
                <input id="registration" name="registration" required>
            </div>
            <button class="btn" type="submit">Add Vehicle</button>
        </form>
    </div>

    <div class="table-container">
        <h3>Your Vehicles</h3>
        <table>
            <thead><tr><th>Type</th><th>Model</th><th>Registration</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars(ucfirst($v['rideType'])); ?></td>
                    <td><?php echo htmlspecialchars($v['model']); ?></td>
                    <td><?php echo htmlspecialchars($v['registration']); ?></td>
                    <td>
                        <span class="status <?php echo $v['status']; ?>">
                            <?php 
                            switch($v['status']) {
                                case 'available': echo 'Available'; break;
                                case 'in_use': echo 'In Use'; break;
                                case 'maintenance': echo 'Maintenance'; break;
                                default: echo htmlspecialchars(ucfirst($v['status'])); break;
                            }
                            ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include '../shared/footer.php'; ?>

