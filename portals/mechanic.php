<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'mechanic') {
    header("Location: ../index.php");
    exit;
}
$mechanic_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_job'])) {
    $stmt = $pdo->prepare("INSERT INTO service_records (vehicle_id, mechanic_id, service_type, cost, mileage_km, status, service_date, notes) VALUES (?,?,?,?,?,?,?,?)");
    $stmt->execute([$_POST['vehicle_id'], $mechanic_id, $_POST['service_type'], $_POST['cost'], $_POST['mileage_km'], $_POST['status'], $_POST['service_date'], $_POST['notes']]);
    $due = date('Y-m-d', strtotime($_POST['service_date'] . ' +3 months'));
    $pdo->prepare("INSERT INTO reminders (vehicle_id, reminder_type, due_date) VALUES (?,?,?)")->execute([$_POST['vehicle_id'], 'Next Service Due', $due]);
    $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?,?)")->execute([$mechanic_id, 'Added service record for vehicle ID ' . $_POST['vehicle_id']]);
    header("Location: mechanic.php");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $pdo->prepare("UPDATE service_records SET status=?, notes=? WHERE id=? AND mechanic_id=?")->execute([$_POST['status'], $_POST['notes'], $_POST['record_id'], $mechanic_id]);
    $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?,?)")->execute([$mechanic_id, 'Updated record ID ' . $_POST['record_id'] . ' to ' . $_POST['status']]);
    header("Location: mechanic.php");
    exit;
}

$allVehicles = $pdo->query("SELECT v.*, u.name as owner_name FROM vehicles v JOIN users u ON v.owner_id=u.id ORDER BY v.id DESC")->fetchAll(PDO::FETCH_ASSOC);

$jobs = $pdo->prepare("SELECT sr.*, v.make, v.model, v.plate_no, u.name as owner_name FROM service_records sr JOIN vehicles v ON sr.vehicle_id=v.id JOIN users u ON v.owner_id=u.id WHERE sr.mechanic_id=? ORDER BY sr.service_date DESC");
$jobs->execute([$mechanic_id]);
$jobs = $jobs->fetchAll(PDO::FETCH_ASSOC);

$pending = count(array_filter($jobs, fn($j) => $j['status'] === 'Pending'));
$inprogress = count(array_filter($jobs, fn($j) => $j['status'] === 'In Progress'));
$done = count(array_filter($jobs, fn($j) => $j['status'] === 'Done'));
$revenue = array_sum(array_column($jobs, 'cost'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mechanic Dashboard - SmartGarage</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700;800&display=swap');
:root{
    --bg:#e7ebf4;
    --bg-soft:#f2f5fb;
    --surface:#e8edf7;
    --text:#24344f;
    --muted:#667896;
    --primary:#2f6dff;
    --primary-light:#5a90ff;
    --primary-dark:#2855d4;
    --success:#2cbd76;
    --danger:#e65f75;
    --warning:#f4b63f;
    --shadow-dark:#c5cee0;
    --shadow-light:#ffffff;
    --radius-md: 16px;
    --radius-lg: 22px;
}
*{box-sizing:border-box;}
body{
    margin:0;
    font-family:'Nunito',sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 8% 4%,#f8f9fd 0%,transparent 40%),
        radial-gradient(circle at 90% 86%,#d6e0f6 0%,transparent 34%),
        linear-gradient(160deg,#e5eaf4 0%,#dde4f1 100%);
    min-height:100vh;
    padding:28px;
}
.page-wrap{max-width:1480px;margin:0 auto;}
.topbar{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    margin-bottom:28px;
    padding:20px 26px;
    border-radius:var(--radius-lg);
    background:var(--surface);
    box-shadow:
        14px 14px 32px var(--shadow-dark),
        -12px -12px 28px var(--shadow-light),
        inset 1px 1px 2px rgba(255,255,255,0.5);
    border:1px solid rgba(255,255,255,0.75);
}
.topbar-left{display:flex;align-items:center;gap:14px;}
.topbar-icon{
    width:52px;height:52px;border-radius:var(--radius-md);
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(145deg,#2f6dff,#5f95ff);
    box-shadow:10px 10px 22px rgba(47,109,255,0.28),-8px -8px 18px rgba(255,255,255,0.92);
    border:1px solid rgba(255,255,255,0.5);
}
.topbar-icon i{color:#fff;font-size:24px;}
.topbar h1{margin:0;font-size:27px;font-weight:800;color:var(--text);}
.topbar p{margin:3px 0 0;color:var(--muted);font-size:13px;font-weight:600;}
.topbar-right{display:flex;align-items:center;gap:16px;}
.user-info{text-align:right;}
.user-info span{display:block;font-size:14px;font-weight:800;color:var(--text);}
.user-info small{font-size:12px;color:var(--muted);font-weight:700;}
.btn-logout,.btn,.btn-primary,.btn-success{
    border:none;
    text-decoration:none;
    display:inline-flex;
    align-items:center;
    justify-content:center;
    gap:7px;
    font-family:'Nunito',sans-serif;
    font-weight:700;
    border-radius:var(--radius-md);
    cursor:pointer;
    transition:0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.btn-logout{
    padding:11px 16px;
    color:#fff;
    background:linear-gradient(145deg,#e65f75,#ef8798);
    box-shadow:8px 8px 16px rgba(230,95,117,0.28),-6px -6px 14px rgba(255,255,255,0.92);
}
.btn-logout:hover{
    transform:translateY(-3px);
    box-shadow:10px 10px 20px rgba(230,95,117,0.32),-8px -8px 18px rgba(255,255,255,0.92);
}
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(200px,1fr));
    gap:14px;
    margin-bottom:24px;
}
.stat-card{
    padding:18px;
    border-radius:var(--radius-lg);
    background:var(--surface);
    box-shadow:
        13px 13px 28px var(--shadow-dark),
        -11px -11px 24px var(--shadow-light),
        inset 1px 1px 2px rgba(255,255,255,0.5);
    border:1px solid rgba(255,255,255,0.75);
    display:flex;
    align-items:center;
    gap:14px;
    transition:0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.stat-card:hover{
    transform:translateY(-4px);
    box-shadow:
        16px 16px 36px var(--shadow-dark),
        -13px -13px 28px var(--shadow-light),
        inset 1px 1px 2px rgba(255,255,255,0.5);
}
.stat-icon{
    width:46px;
    height:46px;
    border-radius:var(--radius-md);
    display:flex;
    align-items:center;
    justify-content:center;
    background:var(--bg-soft);
    box-shadow:inset 4px 4px 10px #cdd5e7,inset -4px -4px 10px #ffffff;
    flex-shrink:0;
    font-size:18px;
}
.si-blue{color:#2f6dff;}
.si-yellow{color:#f4b63f;}
.si-purple{color:#7c3aed;}
.si-green{color:#21945f;}
.si-cyan{color:#0891b2;}
.stat-info h3{margin:0;font-size:24px;font-weight:800;line-height:1.2;color:#2f4f83;}
.stat-info p{margin:2px 0 0;font-size:12px;color:var(--muted);font-weight:700;}
.glass-card{
    padding:26px;
    margin-bottom:24px;
    border-radius:var(--radius-lg);
    background:var(--surface);
    box-shadow:
        13px 13px 28px var(--shadow-dark),
        -11px -11px 24px var(--shadow-light),
        inset 1px 1px 2px rgba(255,255,255,0.5);
    border:1px solid rgba(255,255,255,0.75);
}
.card-header{display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;}
.card-title{margin:0;font-size:20px;font-weight:800;color:#35507a;display:flex;align-items:center;gap:10px;}
.card-title i{color:var(--primary);font-size:22px;}
.form-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px;}
.form-group{display:flex;flex-direction:column;gap:8px;}
.form-group.full{grid-column:1/-1;}
label{font-size:11px;letter-spacing:0.08em;text-transform:uppercase;color:#6d7e9c;font-weight:700;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:13px;top:50%;transform:translateY(-50%);color:#8a96b3;font-size:15px;pointer-events:none;transition:0.3s ease;}
input,select,textarea{
    width:100%;
    padding:12px 13px 12px 40px;
    border:1.5px solid rgba(255,255,255,0.8);
    border-radius:var(--radius-md);
    font-family:'Nunito',sans-serif;
    font-size:14px;
    font-weight:600;
    color:var(--text);
    background:var(--bg-soft);
    box-shadow:6px 6px 14px rgba(0,0,0,0.06),inset 4px 4px 8px rgba(255,255,255,0.9),-2px -2px 6px rgba(255,255,255,0.5);
    outline:none;
    transition:0.3s ease;
}
textarea{padding:12px 13px;min-height:90px;resize:vertical;}
input:hover,
select:hover,
textarea:hover{
    border-color:#a8c5ff;
    box-shadow:6px 6px 14px rgba(0,0,0,0.08),inset 4px 4px 8px rgba(255,255,255,0.9),-2px -2px 6px rgba(255,255,255,0.5);
}
input:focus,
select:focus,
textarea:focus{
    border-color:#2f6dff;
    box-shadow:6px 6px 14px rgba(47,109,255,0.15),inset 4px 4px 8px rgba(255,255,255,0.9),0 0 0 4px rgba(47,109,255,0.15);
}
input:focus ~ i,
select:focus ~ i,
textarea:focus ~ i{
    color:var(--primary);
    transform:translateY(-50%) scale(1.1);
}
select option{color:#24344f;background:#edf2fb;}
input::placeholder,
textarea::placeholder{color:#95a4bf;font-weight:500;}
.btn-primary{
    margin-top:12px;
    padding:11px 18px;
    color:#fff;
    background:linear-gradient(145deg,var(--primary),var(--primary-light));
    box-shadow:9px 9px 20px rgba(47,109,255,0.26),-7px -7px 16px rgba(255,255,255,0.92);
    font-size:13px;
    position:relative;
    overflow:hidden;
}
.btn-primary:before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition:left 0.5s ease;
}
.btn-primary:hover{
    transform:translateY(-2px);
    box-shadow:11px 11px 24px rgba(47,109,255,0.3),-9px -9px 20px rgba(255,255,255,0.93);
}
.btn-primary:hover:before{left:100%;}
.btn-success{
    padding:8px 13px;
    color:#fff;
    background:linear-gradient(145deg,var(--success),#60cd96);
    box-shadow:8px 8px 16px rgba(44,189,118,0.28),-6px -6px 14px rgba(255,255,255,0.92);
    font-size:12px;
}
.btn-success:hover{
    transform:translateY(-2px);
    box-shadow:10px 10px 20px rgba(44,189,118,0.32),-8px -8px 18px rgba(255,255,255,0.92);
}
.table-wrap{overflow-x:auto;border-radius:var(--radius-md);background:var(--bg-soft);padding:2px;margin:0 -2px;}
table{width:100%;border-collapse:collapse;font-size:13px;min-width:1120px;}
th,td{padding:13px 14px;text-align:left;vertical-align:middle;}
th{font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#587097;background:linear-gradient(135deg, #dbe3f3 0%, #eef1f8 100%);font-weight:700;border-bottom:2px solid #c5cfe0;}
td{color:#314767;border-bottom:1px solid #d2daea;background:#fff;}
tr:hover td{background:linear-gradient(90deg, #edf2fb, #f3f6fc);transition:0.2s ease;}
.badge{
    padding:5px 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    display:inline-block;
    letter-spacing:0.05em;
}
.badge-pending{color:#8a5b06;background:#f9e8bf;box-shadow:0 4px 8px rgba(212,164,46,0.2);}
.badge-progress{color:#204f95;background:#cee0ff;box-shadow:0 4px 8px rgba(47,109,255,0.2);}
.badge-done{color:#1c6d47;background:#caefd9;box-shadow:0 4px 8px rgba(44,189,118,0.2);}
.vehicle-info strong{display:block;font-size:13px;font-weight:800;color:var(--text);}
.vehicle-info small{display:block;color:var(--muted);font-size:11px;font-weight:700;margin-top:2px;}
.update-row{display:flex;align-items:center;gap:8px;flex-wrap:wrap;}
.update-row .input-wrap{min-width:130px;flex:1;min-width:100px;}
.update-row input{min-width:160px;}
.empty-state{text-align:center;padding:40px 20px;color:var(--muted);font-weight:700;}
.empty-state i{display:block;margin-bottom:12px;font-size:40px;color:#8a99b4;opacity:0.7;}
@media (max-width:980px){
    body{padding:14px;}
    .topbar{flex-direction:column;align-items:flex-start;}
    .topbar-left{width:100%;}
    .topbar-right{width:100%;justify-content:space-between;}
    .form-grid{grid-template-columns:1fr;}
    .update-row{flex-direction:column;}
    .update-row .input-wrap{width:100%;}
}
</style>
</head>
<body>
<div class="page-wrap">
    <div class="topbar">
        <div class="topbar-left">
            <div class="topbar-icon"><i class="bi bi-wrench-adjustable-circle-fill"></i></div>
            <div>
                <h1>Mechanic Dashboard</h1>
                <p>Service operations and live job updates</p>
            </div>
        </div>
        <div class="topbar-right">
            <div class="user-info">
                <span><?php echo htmlspecialchars($_SESSION['name']); ?></span>
                <small>Mechanic</small>
            </div>
            <a href="../logout.php" class="btn-logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>

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

    <div class="glass-card">
        <div class="card-header">
            <p class="card-title"><i class="bi bi-plus-circle-fill"></i> Log New Service Job</p>
        </div>
        <?php if (count($allVehicles) === 0): ?>
        <div class="empty-state">
            <i class="bi bi-car-front"></i>
            <p>No vehicles in the system yet. Owners need to add vehicles first.</p>
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
                                <?php echo htmlspecialchars($v['make'] . ' ' . $v['model'] . ' (' . $v['plate_no'] . ') - ' . $v['owner_name']); ?>
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
            <button type="submit" name="add_job" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Log Service Job</button>
        </form>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <div class="card-header">
            <p class="card-title"><i class="bi bi-list-check"></i> My Assigned Jobs</p>
        </div>
        <?php if (count($jobs) === 0): ?>
        <div class="empty-state">
            <i class="bi bi-inbox-fill"></i>
            <p>No jobs logged yet. Use the form above to create your first job.</p>
        </div>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Vehicle</th>
                        <th>Owner</th>
                        <th>Service</th>
                        <th>Cost</th>
                        <th>Mileage</th>
                        <th>Date</th>
                        <th>Update Status</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($jobs as $j): ?>
                <tr>
                    <td>
                        <div class="vehicle-info">
                            <strong><?php echo htmlspecialchars($j['make'] . ' ' . $j['model']); ?></strong>
                            <small><?php echo htmlspecialchars($j['plate_no']); ?></small>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars($j['owner_name']); ?></td>
                    <td><?php echo htmlspecialchars($j['service_type']); ?></td>
                    <td>Rs.<?php echo number_format($j['cost'], 2); ?></td>
                    <td><?php echo number_format($j['mileage_km']); ?> km</td>
                    <td><?php echo htmlspecialchars($j['service_date']); ?></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="record_id" value="<?php echo $j['id']; ?>">
                            <div class="update-row">
                                <div class="input-wrap">
                                    <i class="bi bi-flag-fill"></i>
                                    <select name="status">
                                        <option <?php echo $j['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option <?php echo $j['status'] === 'In Progress' ? 'selected' : ''; ?>>In Progress</option>
                                        <option <?php echo $j['status'] === 'Done' ? 'selected' : ''; ?>>Done</option>
                                    </select>
                                </div>
                                <div class="input-wrap">
                                    <i class="bi bi-chat-left-text"></i>
                                    <input type="text" name="notes" value="<?php echo htmlspecialchars($j['notes']); ?>" placeholder="Add note...">
                                </div>
                                <button type="submit" name="update_status" class="btn btn-success"><i class="bi bi-check-lg"></i> Update</button>
                            </div>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

