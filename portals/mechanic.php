<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: ../index.php"); exit;
}
$mechanic_id = $_SESSION['user_id'];

// Add Service Record
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    $stmt = $pdo->prepare("INSERT INTO service_records (vehicle_id, mechanic_id, service_type, cost, mileage_km, status, service_date, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([
        $_POST['vehicle_id'], $mechanic_id,
        $_POST['service_type'], $_POST['cost'],
        $_POST['mileage_km'], $_POST['status'],
        $_POST['service_date'], $_POST['notes']
    ]);

    // Auto create reminder
    $due = date('Y-m-d', strtotime($_POST['service_date'] . ' +3 months'));
    $pdo->prepare("INSERT INTO reminders (vehicle_id, reminder_type, due_date) VALUES (?,?,?)")
        ->execute([$_POST['vehicle_id'], 'Next Service Due', $due]);

    // Log activity
    $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?,?)")
        ->execute([$mechanic_id, 'Added service record for vehicle ID '.$_POST['vehicle_id']]);

    header("Location: mechanic.php"); exit;
}

// Update Job Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $stmt = $pdo->prepare("UPDATE service_records SET status=?, notes=? WHERE id=? AND mechanic_id=?");
    $stmt->execute([$_POST['status'], $_POST['notes'], $_POST['record_id'], $mechanic_id]);

    $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?,?)")
        ->execute([$mechanic_id, 'Updated status to '.$_POST['status'].' for record ID '.$_POST['record_id']]);

    header("Location: mechanic.php"); exit;
}

// Get all vehicles (mechanic can log service for any vehicle)
$allVehicles = $pdo->query("SELECT v.*, u.name as owner_name FROM vehicles v JOIN users u ON v.owner_id=u.id ORDER BY v.id DESC")->fetchAll(PDO::FETCH_ASSOC);

// Get this mechanic's jobs
$jobs = $pdo->prepare("
    SELECT sr.*, v.make, v.model, v.plate_no, u.name as owner_name
    FROM service_records sr
    JOIN vehicles v ON sr.vehicle_id = v.id
    JOIN users u ON v.owner_id = u.id
    WHERE sr.mechanic_id = ?
    ORDER BY sr.service_date DESC
");
$jobs->execute([$mechanic_id]);
$jobs = $jobs->fetchAll(PDO::FETCH_ASSOC);

// Stats
$pending = count(array_filter($jobs, fn($j) => $j['status'] === 'Pending'));
$inprogress = count(array_filter($jobs, fn($j) => $j['status'] === 'In Progress'));
$done = count(array_filter($jobs, fn($j) => $j['status'] === 'Done'));
$revenue = array_sum(array_column($jobs, 'cost'));
?>
<!DOCTYPE html>
<html>
<head>
<title>Mechanic Dashboard - SmartGarage</title>
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
input, select, textarea {
    width:100%; padding:10px;
    background:rgba(255,255,255,0.08);
    border:1px solid rgba(255,255,255,0.15);
    border-radius:8px; color:#e2e8f0;
    margin-bottom:12px; font-size:13px;
}
textarea { height:80px; resize:vertical; }
.form-grid { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
.btn { padding:8px 16px; border-radius:8px; border:none; cursor:pointer; font-size:13px; }
.btn-blue { background:#3b82f6; color:white; }
.btn-green { background:#22c55e; color:white; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { background:rgba(59,130,246,0.3); padding:10px; text-align:left; }
td { padding:10px; border-bottom:1px solid rgba(255,255,255,0.05); vertical-align:top; }
tr:hover { background:rgba(255,255,255,0.03); }
.badge { padding:3px 10px; border-radius:20px; font-size:11px; font-weight:bold; }
.badge-Pending { background:rgba(234,179,8,0.2); color:#fbbf24; }
.badge-In-Progress { background:rgba(59,130,246,0.2); color:#60a5fa; }
.badge-Done { background:rgba(34,197,94,0.2); color:#4ade80; }
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(140px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card {
    background:rgba(255,255,255,0.07);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px; padding:20px; text-align:center;
}
.stat-card h3 { font-size:26px; color:#3b82f6; }
.stat-card p { font-size:12px; color:#94a3b8; margin-top:4px; }
.update-form { display:inline-flex; gap:8px; align-items:center; flex-wrap:wrap; }
.update-form select { width:auto; margin:0; padding:6px 10px; }
.update-form input { width:200px; margin:0; padding:6px 10px; }
.update-form button { padding:6px 12px; }
label { font-size:12px; color:#94a3b8; display:block; margin-bottom:4px; }
</style>
</head>
<body>

<div class="header">
    <h1>🔧 Mechanic Dashboard</h1>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<!-- Stats -->
<div class="stats">
    <div class="stat-card">
        <h3><?php echo count($jobs); ?></h3>
        <p>Total Jobs</p>
    </div>
    <div class="stat-card">
        <h3 style="color:#fbbf24;"><?php echo $pending; ?></h3>
        <p>Pending</p>
    </div>
    <div class="stat-card">
        <h3 style="color:#60a5fa;"><?php echo $inprogress; ?></h3>
        <p>In Progress</p>
    </div>
    <div class="stat-card">
        <h3 style="color:#4ade80;"><?php echo $done; ?></h3>
        <p>Done</p>
    </div>
    <div class="stat-card">
        <h3>₹<?php echo number_format($revenue,2); ?></h3>
        <p>Revenue Generated</p>
    </div>
</div>

<!-- Add New Service Job -->
<div class="glass-card">
    <p class="section-title">➕ Log New Service Job</p>
    <?php if (count($allVehicles) === 0): ?>
        <p style="color:#94a3b8;">No vehicles in system yet. Owner must add vehicles first.</p>
    <?php else: ?>
    <form method="POST">
        <div class="form-grid">
            <div>
                <label>Select Vehicle</label>
                <select name="vehicle_id" required>
                    <option value="">-- Select Vehicle --</option>
                    <?php foreach ($allVehicles as $v): ?>
                    <option value="<?php echo $v['id']; ?>">
                        <?php echo htmlspecialchars($v['make'].' '.$v['model'].' ('.$v['plate_no'].') — Owner: '.$v['owner_name']); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Service Type</label>
                <select name="service_type" required>
                    <option>Oil Change</option>
                    <option>Tyre Rotation</option>
                    <option>Brake Inspection</option>
                    <option>Engine Tune-up</option>
                    <option>AC Service</option>
                    <option>Battery Replacement</option>
                    <option>Full Service</option>
                    <option>Other</option>
                </select>
            </div>
            <div>
                <label>Cost (₹)</label>
                <input type="number" name="cost" placeholder="e.g. 1500" step="0.01" required>
            </div>
            <div>
                <label>Mileage (km)</label>
                <input type="number" name="mileage_km" placeholder="e.g. 45000" required>
            </div>
            <div>
                <label>Service Date</label>
                <input type="date" name="service_date" value="<?php echo date('Y-m-d'); ?>" required>
            </div>
            <div>
                <label>Status</label>
                <select name="status">
                    <option>Pending</option>
                    <option>In Progress</option>
                    <option>Done</option>
                </select>
            </div>
        </div>
        <label>Notes</label>
        <textarea name="notes" placeholder="Any additional notes about the service..."></textarea>
        <button type="submit" name="add_job" class="btn btn-blue">Log Service Job</button>
    </form>
    <?php endif; ?>
</div>

<!-- My Jobs -->
<div class="glass-card">
    <p class="section-title">📋 My Assigned Jobs</p>
    <?php if (count($jobs) === 0): ?>
        <p style="color:#94a3b8;">No jobs logged yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Vehicle</th><th>Owner</th><th>Service</th>
            <th>Cost</th><th>Date</th><th>Status & Notes</th>
        </tr>
        <?php foreach ($jobs as $j): ?>
        <tr>
            <td>
                <?php echo htmlspecialchars($j['make'].' '.$j['model']); ?><br>
                <small style="color:#94a3b8;"><?php echo htmlspecialchars($j['plate_no']); ?></small>
            </td>
            <td><?php echo htmlspecialchars($j['owner_name']); ?></td>
            <td><?php echo htmlspecialchars($j['service_type']); ?></td>
            <td>₹<?php echo number_format($j['cost'],2); ?></td>
            <td><?php echo $j['service_date']; ?></td>
            <td>
                <form method="POST" class="update-form">
                    <input type="hidden" name="record_id" value="<?php echo $j['id']; ?>">
                    <select name="status">
                        <option <?php echo $j['status']==='Pending'?'selected':''; ?>>Pending</option>
                        <option <?php echo $j['status']==='In Progress'?'selected':''; ?>>In Progress</option>
                        <option <?php echo $j['status']==='Done'?'selected':''; ?>>Done</option>
                    </select>
                    <input type="text" name="notes" value="<?php echo htmlspecialchars($j['notes']); ?>" placeholder="Notes">
                    <button type="submit" name="update_status" class="btn btn-green">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

</body>
</html>

