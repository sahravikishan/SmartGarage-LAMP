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
    $stmt->execute([$_POST['vehicle_id'], $mechanic_id, $_POST['service_type'], $_POST['cost'], $_POST['mileage_km'], $_POST['status'], $_POST['service_date'], $_POST['notes']]);
    $due = date('Y-m-d', strtotime($_POST['service_date'] . ' +3 months'));
    $pdo->prepare("INSERT INTO reminders (vehicle_id, reminder_type, due_date) VALUES (?,?,?)")->execute([$_POST['vehicle_id'], 'Next Service Due', $due]);
    $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?,?)")->execute([$mechanic_id, 'Added service record for vehicle ID '.$_POST['vehicle_id']]);
    header("Location: mechanic.php"); exit;
}

// Update Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE service_records SET status=?, notes=? WHERE id=? AND mechanic_id=?")->execute([$_POST['status'], $_POST['notes'], $_POST['record_id'], $mechanic_id]);
    $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?,?)")->execute([$mechanic_id, 'Updated record ID '.$_POST['record_id'].' to '.$_POST['status']]);
    header("Location: mechanic.php"); exit;
}

$allVehicles = $pdo->query("SELECT v.*, u.name as owner_name FROM vehicles v JOIN users u ON v.owner_id=u.id ORDER BY v.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$jobs = $pdo->prepare("SELECT sr.*, v.make, v.model, v.plate_no, u.name as owner_name FROM service_records sr JOIN vehicles v ON sr.vehicle_id=v.id JOIN users u ON v.owner_id=u.id WHERE sr.mechanic_id=? ORDER BY sr.service_date DESC");
$jobs->execute([$mechanic_id]);
$jobs = $jobs->fetchAll(PDO::FETCH_ASSOC);

$pending    = count(array_filter($jobs, fn($j) => $j['status'] === 'Pending'));
$inprogress = count(array_filter($jobs, fn($j) => $j['status'] === 'In Progress'));
$done       = count(array_filter($jobs, fn($j) => $j['status'] === 'Done'));
$revenue    = array_sum(array_column($jobs, 'cost'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mechanic Dashboard — SmartGarage</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f3460 100%);min-height:100vh;color:#e2e8f0;padding:24px;}
.topbar{display:flex;justify-content:space-between;align-items:center;margin-bottom:28px;padding:16px 24px;background:rgba(255,255,255,0.04);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.08);border-radius:16px;}
.topbar-left{display:flex;align-items:center;gap:12px;}
.topbar-icon{width:40px;height:40px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:10px;display:flex;align-items:center;justify-content:center;}
.topbar-icon i{font-size:20px;color:white;}
.topbar h1{font-size:20px;font-weight:700;}
.topbar p{font-size:12px;color:#64748b;margin-top:2px;}
.topbar-right{display:flex;align-items:center;gap:12px;}
.user-info{text-align:right;}
.user-info span{display:block;font-size:13px;font-weight:600;}
.user-info small{font-size:11px;color:#64748b;}
.btn-logout{display:flex;align-items:center;gap:6px;padding:8px 16px;background:rgba(239,68,68,0.15);border:1px solid rgba(239,68,68,0.3);color:#f87171;border-radius:8px;text-decoration:none;font-size:13px;font-weight:500;transition:all 0.2s;}
.btn-logout:hover{background:rgba(239,68,68,0.25);}
.stats{display:grid;grid-template-columns:repeat(5,1fr);gap:16px;margin-bottom:24px;}
.stat-card{background:rgba(255,255,255,0.05);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:20px;display:flex;align-items:center;gap:14px;transition:all 0.3s;}
.stat-card:hover{background:rgba(255,255,255,0.08);transform:translateY(-2px);}
.stat-icon{width:44px;height:44px;border-radius:12px;display:flex;align-items:center;justify-content:center;flex-shrink:0;}
.stat-icon i{font-size:20px;}
.si-blue{background:rgba(59,130,246,0.2);color:#60a5fa;}
.si-yellow{background:rgba(234,179,8,0.2);color:#fbbf24;}
.si-purple{background:rgba(168,85,247,0.2);color:#c084fc;}
.si-green{background:rgba(34,197,94,0.2);color:#4ade80;}
.si-cyan{background:rgba(6,182,212,0.2);color:#22d3ee;}
.stat-info h3{font-size:22px;font-weight:700;}
.stat-info p{font-size:11px;color:#64748b;margin-top:2px;}
.glass-card{background:rgba(255,255,255,0.04);backdrop-filter:blur(16px);border:1px solid rgba(255,255,255,0.08);border-radius:16px;padding:24px;margin-bottom:20px;}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;}
.card-title{display:flex;align-items:center;gap:10px;font-size:16px;font-weight:600;color:#93c5fd;}
.card-title i{font-size:18px;}
.form-grid{display:grid;grid-template-columns:1fr 1fr 1fr;gap:14px;}
.form-group{display:flex;flex-direction:column;gap:6px;}
.form-group.full{grid-column:1/-1;}
label{font-size:11px;font-weight:600;color:#94a3b8;letter-spacing:0.5px;text-transform:uppercase;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:12px;top:50%;transform:translateY(-50%);color:#475569;font-size:14px;pointer-events:none;}
input,.input-wrap input,select,textarea{width:100%;padding:10px 14px 10px 36px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:#f1f5f9;font-size:13px;font-family:'Inter',sans-serif;outline:none;transition:all 0.3s;}
select{padding-left:36px;}
textarea{padding:10px 14px;height:80px;resize:vertical;}
input:focus,select:focus,textarea:focus{border-color:#3b82f6;background:rgba(59,130,246,0.08);box-shadow:0 0 0 3px rgba(59,130,246,0.15);}
input::placeholder,textarea::placeholder{color:#475569;}
select option{background:#1e293b;}
.btn{display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:10px;border:none;cursor:pointer;font-size:13px;font-weight:600;font-family:'Inter',sans-serif;transition:all 0.2s;}
.btn-primary{background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;}
.btn-primary:hover{transform:translateY(-1px);box-shadow:0 6px 16px rgba(59,130,246,0.4);}
.btn-success{background:linear-gradient(135deg,#22c55e,#16a34a);color:white;padding:7px 14px;font-size:12px;}
.btn-success:hover{transform:translateY(-1px);box-shadow:0 4px 12px rgba(34,197,94,0.3);}
table{width:100%;border-collapse:collapse;font-size:13px;}
thead tr{background:rgba(59,130,246,0.15);border-bottom:1px solid rgba(59,130,246,0.2);}
th{padding:12px 14px;text-align:left;font-size:11px;font-weight:600;color:#93c5fd;letter-spacing:0.5px;text-transform:uppercase;}
td{padding:14px;border-bottom:1px solid rgba(255,255,255,0.04);vertical-align:middle;}
tr:hover td{background:rgba(255,255,255,0.02);}
.badge{display:inline-flex;align-items:center;gap:4px;padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;}
.badge-pending{background:rgba(234,179,8,0.15);color:#fbbf24;border:1px solid rgba(234,179,8,0.2);}
.badge-progress{background:rgba(59,130,246,0.15);color:#60a5fa;border:1px solid rgba(59,130,246,0.2);}
.badge-done{background:rgba(34,197,94,0.15);color:#4ade80;border:1px solid rgba(34,197,94,0.2);}
.vehicle-info strong{display:block;font-size:13px;}
.vehicle-info small{font-size:11px;color:#64748b;}
.update-row{display:flex;align-items:center;gap:8px;flex-wrap:nowrap;}
.update-row select{width:130px;padding:7px 10px 7px 32px;font-size:12px;}
.update-row input{width:180px;padding:7px 10px 7px 32px;font-size:12px;}
.empty-state{text-align:center;padding:40px 20px;color:#475569;}
.empty-state i{font-size:40px;margin-bottom:12px;display:block;color:#334155;}
.empty-state p{font-size:14px;}
</style>
</head>
<body>

<!-- Top Bar -->
<div class="topbar">
    <div class="topbar-left">
        <div class="topbar-icon"><i class="bi bi-wrench-adjustable-circle-fill"></i></div>
        <div>
            <h1>Mechanic Dashboard</h1>
            <p>SmartGarage Service Management</p>
        </div>
    </div>
    <div class="topbar-right">
        <div class="user-info">
            <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
            <small>Mechanic</small>
        </div>
        <a href="../logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>

<!-- Stats -->
<div class="stats">
    <div class="stat-card">
        <div class="stat-icon si-blue"><i class="bi bi-clipboard2-check-fill"></i></div>
        <div class="stat-info"><h3><?php echo count($jobs); ?></h3><p>Total Jobs</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-yellow"><i class="bi bi-hourglass-split"></i></div>
        <div class="stat-info"><h3><?php echo $pending; ?></h3><p>Pending</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-purple"><i class="bi bi-arrow-repeat"></i></div>
        <div class="stat-info"><h3><?php echo $inprogress; ?></h3><p>In Progress</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-green"><i class="bi bi-check-circle-fill"></i></div>
        <div class="stat-info"><h3><?php echo $done; ?></h3><p>Completed</p></div>
    </div>
    <div class="stat-card">
        <div class="stat-icon si-cyan"><i class="bi bi-currency-rupee"></i></div>
        <div class="stat-info"><h3><?php echo number_format($revenue); ?></h3><p>Revenue</p></div>
    </div>
</div>

<!-- Add Job Form -->
<div class="glass-card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-plus-circle-fill"></i> Log New Service Job</div>
    </div>
    <?php if (count($allVehicles) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-car-front"></i>
        <p>No vehicles in system yet. Owner must add vehicles first.</p>
    </div>
    <?php else: ?>
    <form method="POST">
        <div class="form-grid">
            <div class="form-group">
                <label>Select Vehicle</label>
                <div class="input-wrap">
                    <i class="bi bi-car-front-fill"></i>
                    <select name="vehicle_id" required>
                        <option value="">-- Select Vehicle --</option>
                        <?php foreach ($allVehicles as $v): ?>
                        <option value="<?php echo $v['id']; ?>">
                            <?php echo htmlspecialchars($v['make'].' '.$v['model'].' ('.$v['plate_no'].') — '.$v['owner_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Service Type</label>
                <div class="input-wrap">
                    <i class="bi bi-tools"></i>
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
            </div>
            <div class="form-group">
                <label>Status</label>
                <div class="input-wrap">
                    <i class="bi bi-flag-fill"></i>
                    <select name="status">
                        <option>Pending</option>
                        <option>In Progress</option>
                        <option>Done</option>
                    </select>
                </div>
            </div>
            <div class="form-group">
                <label>Cost (Rs.)</label>
                <div class="input-wrap">
                    <i class="bi bi-currency-rupee"></i>
                    <input type="number" name="cost" placeholder="e.g. 1500" step="0.01" required>
                </div>
            </div>
            <div class="form-group">
                <label>Mileage (km)</label>
                <div class="input-wrap">
                    <i class="bi bi-speedometer2"></i>
                    <input type="number" name="mileage_km" placeholder="e.g. 45000" required>
                </div>
            </div>
            <div class="form-group">
                <label>Service Date</label>
                <div class="input-wrap">
                    <i class="bi bi-calendar3"></i>
                    <input type="date" name="service_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
            </div>
            <div class="form-group full">
                <label>Notes</label>
                <textarea name="notes" placeholder="Describe the service work done..."></textarea>
            </div>
        </div>
        <button type="submit" name="add_job" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Log Service Job
        </button>
    </form>
    <?php endif; ?>
</div>

<!-- Jobs Table -->
<div class="glass-card">
    <div class="card-header">
        <div class="card-title"><i class="bi bi-list-check"></i> My Assigned Jobs</div>
    </div>
    <?php if (count($jobs) === 0): ?>
    <div class="empty-state">
        <i class="bi bi-inbox-fill"></i>
        <p>No jobs logged yet. Use the form above to log your first job.</p>
    </div>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th><i class="bi bi-car-front"></i> Vehicle</th>
                <th><i class="bi bi-person"></i> Owner</th>
                <th><i class="bi bi-tools"></i> Service</th>
                <th><i class="bi bi-currency-rupee"></i> Cost</th>
                <th><i class="bi bi-speedometer2"></i> Mileage</th>
                <th><i class="bi bi-calendar3"></i> Date</th>
                <th><i class="bi bi-pencil-square"></i> Update Status</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($jobs as $j): ?>
        <tr>
            <td>
                <div class="vehicle-info">
                    <strong><?php echo htmlspecialchars($j['make'].' '.$j['model']); ?></strong>
                    <small><?php echo htmlspecialchars($j['plate_no']); ?></small>
                </div>
            </td>
            <td><?php echo htmlspecialchars($j['owner_name']); ?></td>
            <td><?php echo htmlspecialchars($j['service_type']); ?></td>
            <td>Rs.<?php echo number_format($j['cost'],2); ?></td>
            <td><?php echo number_format($j['mileage_km']); ?> km</td>
            <td><?php echo $j['service_date']; ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="record_id" value="<?php echo $j['id']; ?>">
                    <div class="update-row">
                        <div class="input-wrap">
                            <i class="bi bi-flag-fill"></i>
                            <select name="status">
                                <option <?php echo $j['status']==='Pending'?'selected':''; ?>>Pending</option>
                                <option <?php echo $j['status']==='In Progress'?'selected':''; ?>>In Progress</option>
                                <option <?php echo $j['status']==='Done'?'selected':''; ?>>Done</option>
                            </select>
                        </div>
                        <div class="input-wrap">
                            <i class="bi bi-chat-left-text"></i>
                            <input type="text" name="notes" value="<?php echo htmlspecialchars($j['notes']); ?>" placeholder="Add note...">
                        </div>
                        <button type="submit" name="update_status" class="btn btn-success">
                            <i class="bi bi-check-lg"></i> Update
                        </button>
                    </div>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

</body>
</html>


