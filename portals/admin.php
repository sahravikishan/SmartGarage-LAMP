<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../index.php"); exit;
}

// Stats
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalVehicles = $pdo->query("SELECT COUNT(*) FROM vehicles")->fetchColumn();
$totalJobs = $pdo->query("SELECT COUNT(*) FROM service_records")->fetchColumn();
$totalRevenue = $pdo->query("SELECT COALESCE(SUM(cost),0) FROM service_records")->fetchColumn();
$monthJobs = $pdo->query("SELECT COUNT(*) FROM service_records WHERE MONTH(service_date)=MONTH(CURDATE()) AND YEAR(service_date)=YEAR(CURDATE())")->fetchColumn();

// Users list
$users = $pdo->query("SELECT * FROM users ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Service records
$records = $pdo->query("SELECT sr.*, u.name as mechanic_name, v.make, v.model, v.plate_no FROM service_records sr JOIN users u ON sr.mechanic_id=u.id JOIN vehicles v ON sr.vehicle_id=v.id ORDER BY sr.service_date DESC")->fetchAll(PDO::FETCH_ASSOC);

// Most common service
$commonService = $pdo->query("SELECT service_type, COUNT(*) as cnt FROM service_records GROUP BY service_type ORDER BY cnt DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Handle user delete
if (isset($_GET['delete_user'])) {
    $pdo->prepare("DELETE FROM users WHERE id=?")->execute([$_GET['delete_user']]);
    header("Location: admin.php"); exit;
}

// Export CSV
if (isset($_GET['export'])) {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="smartgarage_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID','Vehicle','Plate','Mechanic','Service','Cost','Mileage','Status','Date']);
    foreach ($records as $r) {
        fputcsv($out, [$r['id'], $r['make'].' '.$r['model'], $r['plate_no'], $r['mechanic_name'], $r['service_type'], $r['cost'], $r['mileage_km'], $r['status'], $r['service_date']]);
    }
    fclose($out); exit;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Admin Dashboard - SmartGarage</title>
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
.stats { display:grid; grid-template-columns:repeat(auto-fit,minmax(160px,1fr)); gap:16px; margin-bottom:24px; }
.stat-card {
    background:rgba(255,255,255,0.07);
    border:1px solid rgba(255,255,255,0.1);
    border-radius:12px; padding:20px; text-align:center;
}
.stat-card h3 { font-size:28px; color:#3b82f6; }
.stat-card p { font-size:12px; color:#94a3b8; margin-top:4px; }
table { width:100%; border-collapse:collapse; font-size:13px; }
th { background:rgba(59,130,246,0.3); padding:10px; text-align:left; }
td { padding:10px; border-bottom:1px solid rgba(255,255,255,0.05); }
tr:hover { background:rgba(255,255,255,0.03); }
.badge {
    padding:3px 10px; border-radius:20px; font-size:11px; font-weight:bold;
}
.badge-pending { background:rgba(234,179,8,0.2); color:#fbbf24; }
.badge-progress { background:rgba(59,130,246,0.2); color:#60a5fa; }
.badge-done { background:rgba(34,197,94,0.2); color:#4ade80; }
.badge-owner { background:rgba(168,85,247,0.2); color:#c084fc; }
.badge-mechanic { background:rgba(59,130,246,0.2); color:#60a5fa; }
.badge-admin { background:rgba(239,68,68,0.2); color:#f87171; }
.btn { padding:6px 14px; border-radius:6px; text-decoration:none; font-size:12px; border:none; cursor:pointer; }
.btn-red { background:#ef4444; color:white; }
.btn-green { background:#22c55e; color:white; }
.section-title { font-size:18px; margin-bottom:16px; color:#93c5fd; }
.export-btn {
    background:#22c55e; color:white; padding:8px 20px;
    border-radius:8px; text-decoration:none; font-size:13px; float:right;
}
</style>
</head>
<body>

<div class="header">
    <h1>⚙️ Admin Dashboard</h1>
    <a href="../logout.php" class="logout">Logout</a>
</div>

<!-- Stats Cards -->
<div class="stats">
    <div class="stat-card">
        <h3><?php echo $totalUsers; ?></h3>
        <p>Total Users</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $totalVehicles; ?></h3>
        <p>Total Vehicles</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $totalJobs; ?></h3>
        <p>Total Jobs</p>
    </div>
    <div class="stat-card">
        <h3>₹<?php echo number_format($totalRevenue,2); ?></h3>
        <p>Total Revenue</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $monthJobs; ?></h3>
        <p>Jobs This Month</p>
    </div>
    <div class="stat-card">
        <h3><?php echo $commonService ? $commonService['cnt'] : 0; ?></h3>
        <p><?php echo $commonService ? $commonService['service_type'] : 'No data'; ?></p>
    </div>
</div>

<!-- Users Table -->
<div class="glass-card">
    <p class="section-title">👥 Manage Users</p>
    <table>
        <tr>
            <th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th>Action</th>
        </tr>
        <?php foreach ($users as $u): ?>
        <tr>
            <td><?php echo $u['id']; ?></td>
            <td><?php echo htmlspecialchars($u['name']); ?></td>
            <td><?php echo htmlspecialchars($u['email']); ?></td>
            <td><span class="badge badge-<?php echo $u['role']; ?>"><?php echo strtoupper($u['role']); ?></span></td>
            <td><?php echo $u['created_at']; ?></td>
            <td>
                <?php if ($u['id'] != $_SESSION['user_id']): ?>
                <a href="admin.php?delete_user=<?php echo $u['id']; ?>"
                   class="btn btn-red"
                   onclick="return confirm('Delete this user?')">Delete</a>
                <?php else: ?>
                <span style="color:#94a3b8;font-size:11px;">You</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div>

<!-- Service Records -->
<div class="glass-card">
    <a href="admin.php?export=1" class="export-btn">⬇ Export CSV</a>
    <p class="section-title">🔧 All Service Records</p>
    <?php if (count($records) === 0): ?>
        <p style="color:#94a3b8;">No service records yet.</p>
    <?php else: ?>
    <table>
        <tr>
            <th>Vehicle</th><th>Plate</th><th>Mechanic</th>
            <th>Service</th><th>Cost</th><th>Status</th><th>Date</th>
        </tr>
        <?php foreach ($records as $r): ?>
        <tr>
            <td><?php echo htmlspecialchars($r['make'].' '.$r['model']); ?></td>
            <td><?php echo htmlspecialchars($r['plate_no']); ?></td>
            <td><?php echo htmlspecialchars($r['mechanic_name']); ?></td>
            <td><?php echo htmlspecialchars($r['service_type']); ?></td>
            <td>₹<?php echo number_format($r['cost'],2); ?></td>
            <td>
                <span class="badge badge-<?php echo strtolower(str_replace(' ','',$r['status'])); ?>">
                    <?php echo $r['status']; ?>
                </span>
            </td>
            <td><?php echo $r['service_date']; ?></td>
        </tr>
        <?php endforeach; ?>
    </table>
    <?php endif; ?>
</div>

</body>
</html>


