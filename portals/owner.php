<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../index.php"); exit;
}
$owner_id = $_SESSION['user_id'];

// Add Vehicle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $stmt = $pdo->prepare("INSERT INTO vehicles (owner_id, make, model, year, plate_no) VALUES (?,?,?,?,?)");
    $stmt->execute([$owner_id, $_POST['make'], $_POST['model'], $_POST['year'], $_POST['plate_no']]);
    header("Location: owner.php"); exit;
}

// Delete Vehicle
if (isset($_GET['delete_vehicle'])) {
    $pdo->prepare("DELETE FROM vehicles WHERE id=? AND owner_id=?")->execute([$_GET['delete_vehicle'], $owner_id]);
    header("Location: owner.php"); exit;
}

// Get owner vehicles
$vehicles = $pdo->prepare("SELECT * FROM vehicles WHERE owner_id=? ORDER BY id DESC");
$vehicles->execute([$owner_id]);
$vehicles = $vehicles->fetchAll(PDO::FETCH_ASSOC);

// Get service records for owner vehicles
$records = $pdo->prepare("
    SELECT sr.*, v.make, v.model, v.plate_no, u.name as mechanic_name
    FROM service_records sr
    JOIN vehicles v ON sr.vehicle_id = v.id
    JOIN users u ON sr.mechanic_id = u.id
    WHERE v.owner_id = ?
    ORDER BY sr.service_date DESC
");
$records->execute([$owner_id]);
$records = $records->fetchAll(PDO::FETCH_ASSOC);

// Get reminders
$reminders = $pdo->prepare("
    SELECT r.*, v.make, v.model, v.plate_no
    FROM reminders r
    JOIN vehicles v ON r.vehicle_id = v.id
    WHERE v.owner_id = ?
    ORDER BY r.due_date ASC
");
$reminders->execute([$owner_id]);
$reminders = $reminders->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html>
<head>
<title>Owner Dashboard - SmartGarage</title>
<style>
* { margin:0; padding:0; box-sizing:border-box; }
body {
    background: linear-gradient(135deg, #0f172a, #1e293b, #0f3460);
    min-height:100vh; font-family:Arial,sans-serif;
    color:#e2e8f0; padding:30px;
}
.glass-card {
    background:rgba(255,255,255,0.05);
    backdrop-filter:blur(16px);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:16px; padding:24px; margin-bottom:20px;
}
.header { display:flex; justify-content:space-between; align-items:center; margin-bottom:24px; }
.logout { background:#ef4444; color:white; padding:8px 16px; border-radius:8px; text-decoration:none; }
.section-title { font-size:18px; margin-bottom:16px; color:#93c5fd; }
input, select {
    width:100%; padding:10px;
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.15);
    border-radius:8px; color:#e2e8f0;
    margin-bottom:12px; font-size:13px;
}
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; text-decoration:none; display:inline-block; }
.btn-blue { background:#3b82f6; color:white; }
.btn-red { background:#ef4444; color:white; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { background:rgba(59,130,246,0.3); padding:10px; text-align:left; }
td { padding:10px; border-bottom:1px solid rgba(255,255,255,0.05); }
tr:hover { background:rgba(255,255,255,0.03); }
.badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:bold; }
.badge-Pending { background:rgba(234,179,8,0.2); color:#fbbf24; }
.badge-In-Progress { background:rgba(59,130,246,0.2); color:#60a5fa; }
.badge-Done { background:rgba(34,197,94,0.2); color:#4ade80; }
.reminder-card {
    background:rgba(239,68,68,0.1);
    border:1px solid rgba(239,68,68,0.3);
    border-radius:10px; padding:14px; margin-bottom:10px;
}
.reminder-card.soon {
    background:rgba(234,179,8,0.1);
    border-color:rgba(234,179,8,0.3);
}
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card {
    background:rgba(255,255,255,0.07);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px; padding:20px; text-align:center;
}
.stat-card h3 { font-size:26px; color:#3b82f6; }
.stat-card p { font-size:12px; color:#94a3b8; margin-top:4px; }
</style>
</head>
<body>

<div class="header">
    <h1>🚗 Owner Dashboard</h1>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<!-- Stats -->
<div class="stats">
    <div class="stat-card">
        <h3><?php echo count($vehicles); ?></h3>
        <p>My Vehicles</p>
    </div>
    <div class="stat-card">
        <h3><?php echo count($records); ?></h3>
        <p>Service Records</p>
    </div>
    <div class="stat-card">
        <h3>₹<?php echo number_format(array_sum(array_column($records,'cost')),2); ?></h3>
        <p>Total Spent</p>
    </div>
    <div class="stat-card">
        <h3><?php echo count($reminders); ?></h3>
        <p>Reminders</p>
    </div>
</div>

<!-- Add Vehicle Form -->
<div class="glass-card">
    <p class="section-title">➕ Add New Vehicle</p>
    <form method="POST">
        <div class="form-grid">
            <input type="text" name="make" placeholder="Make (e.g. Toyota)" required>
            <input type="text" name="model" placeholder="Model (e.g. Innova)" required>
            <input type="number" name="year" placeholder="Year (e.g. 2022)" min="1990" max="2026" required>
            <input type="text" name="plate_no" placeholder="Plate No (e.g. MH12AB1234)" required>
        </div>
        <button type="submit" name="add_vehicle" class="btn btn-blue">Add Vehicle</button>
    </form>
</div>

<!-- My Vehicles -->
<div class="glass-card">
    <p class="section-title">🚙 My Vehicles</p>
    <?php if (count($vehicles) === 0): ?>
        <p style="color:#94a3b8;">No vehicles added yet.</p>
    <?php else: ?>
    <table>
        <tr><th>Make</th><th>Model</th><th>Year</th><th>Plate No</th><th>Action</th></tr>
        <?php foreach ($vehicles as $v): ?>
        <tr>
            <td><?php echo htmlspecialchars($v['make']); ?></td>
            <td><?php echo htmlspecialchars($v['model']); ?></td>
            <td><?php echo $v['year']; ?></td>
            <td><?php echo htmlspecialchars($v['plate_no']); ?></td>
            <td>
                <a href="owner.php?delete_vehicle=<?php echo $v['id']; ?>"
                   class="btn btn-red"
                   onclick="return confirm('Delete this vehicle?')">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<!-- Service History -->
<div class="glass-card">
    <p class="section-title">🔧 Service History</p>
    <?php if (count($records) === 0): ?>
        <p style="color:#94a3b8;">No service records yet.</p>
    <?php else: ?>
    <table>
        <tr><th>Vehicle</th><th>Plate</th><th>Mechanic</th><th>Service</th><th>Cost</th><th>Mileage</th><th>Status</th><th>Date</th></tr>
        <?php foreach ($records as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['make'].' '.$r['model']); ?></td>
            <td><?php echo htmlspecialchars($r['plate_no']); ?></td>
            <td><?php echo htmlspecialchars($r['mechanic_name']); ?></td>
            <td><?php echo htmlspecialchars($r['service_type']); ?></td>
            <td>₹<?php echo number_format($r['cost'],2); ?></td>
            <td><?php echo number_format($r['mileage_km']); ?> km</td>
            <td>
                <span class="badge badge-<?php echo str_replace(' ','-',$r['status']); ?>">
                    <?php echo $r['status']; ?>
                </span>
            </td>
            <td><?php echo $r['service_date']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

<!-- Reminders -->
<div class="glass-card">
    <p class="section-title">🔔 Upcoming Reminders</p>
    <?php if (count($reminders) === 0): ?>
        <p style="color:#94a3b8;">No reminders yet.</p>
    <?php else: ?>
        <?php foreach ($reminders as $rem):
            $daysLeft = (strtotime($rem['due_date']) - time()) / 86400;
            $cls = $daysLeft < 7 ? '' : 'soon';
        ?>
        <div class="reminder-card <?php echo $cls; ?>">
            <strong><?php echo htmlspecialchars($rem['make'].' '.$rem['model']); ?></strong>
            (<?php echo htmlspecialchars($rem['plate_no']); ?>) —
            <?php echo htmlspecialchars($rem['reminder_type']); ?> due on
            <strong><?php echo $rem['due_date']; ?></strong>
            (<?php echo round($daysLeft); ?> days left)
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

</body>
</html>


