<?php
session_start();
require '../includes/db.php';
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'owner') {
    header("Location: ../index.php");
    exit;
}
$owner_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_vehicle'])) {
    $stmt = $pdo->prepare("INSERT INTO vehicles (owner_id, make, model, year, plate_no) VALUES (?,?,?,?,?)");
    $stmt->execute([$owner_id, $_POST['make'], $_POST['model'], $_POST['year'], $_POST['plate_no']]);
    header("Location: owner.php");
    exit;
}

if (isset($_GET['delete_vehicle'])) {
    $pdo->prepare("DELETE FROM vehicles WHERE id=? AND owner_id=?")->execute([$_GET['delete_vehicle'], $owner_id]);
    header("Location: owner.php");
    exit;
}

$vehicles = $pdo->prepare("SELECT * FROM vehicles WHERE owner_id=? ORDER BY id DESC");
$vehicles->execute([$owner_id]);
$vehicles = $vehicles->fetchAll(PDO::FETCH_ASSOC);

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
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Owner Dashboard - SmartGarage</title>
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
        radial-gradient(circle at 90% 88%,#d6e0f6 0%,transparent 34%),
        linear-gradient(160deg,#e5eaf4 0%,#dde4f1 100%);
    min-height:100vh;
    padding:28px;
}
.page-wrap{max-width:1380px;margin:0 auto;}
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    gap:16px;
    padding:20px 26px;
    margin-bottom:28px;
    border-radius:var(--radius-lg);
    background:var(--surface);
    box-shadow:
        14px 14px 32px var(--shadow-dark),
        -12px -12px 28px var(--shadow-light),
        inset 1px 1px 2px rgba(255,255,255,0.5);
    border:1px solid rgba(255,255,255,0.75);
}
.header h1{margin:0;font-size:28px;font-weight:800;display:flex;align-items:center;gap:12px;}
.header h1 i{color:var(--primary);font-size:32px;}
.header-meta{margin:4px 0 0;color:var(--muted);font-size:13px;font-weight:600;}
.btn, .logout{
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
.logout{
    padding:11px 16px;
    color:#fff;
    background:linear-gradient(145deg,#e65f75,#ef8798);
    box-shadow:8px 8px 16px rgba(230,95,117,0.28),-6px -6px 14px rgba(255,255,255,0.92);
}
.logout:hover{
    transform:translateY(-3px);
    box-shadow:10px 10px 20px rgba(230,95,117,0.32),-8px -8px 18px rgba(255,255,255,0.92);
}
.stats{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:16px;
    margin-bottom:24px;
}
.stat-card{
    padding:20px;
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
    width:48px;
    height:48px;
    border-radius:var(--radius-md);
    display:flex;
    align-items:center;
    justify-content:center;
    color:var(--primary);
    background:var(--bg-soft);
    box-shadow:inset 4px 4px 10px #cdd5e7,inset -4px -4px 10px #ffffff;
    flex-shrink:0;
    font-size:20px;
}
.stat-card h3{margin:0;font-size:26px;font-weight:800;line-height:1.1;color:#2f4f83;}
.stat-card p{margin:2px 0 0;font-size:12px;color:var(--muted);font-weight:700;}
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
.section-title{
    margin:0 0 16px;
    color:#35507a;
    font-size:20px;
    font-weight:800;
    display:flex;
    align-items:center;
    gap:10px;
}
.section-title i{color:var(--primary);font-size:22px;}
.form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;}
input,select{
    width:100%;
    padding:13px 14px;
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
input:hover,
select:hover{
    border-color:#a8c5ff;
    box-shadow:6px 6px 14px rgba(0,0,0,0.08),inset 4px 4px 8px rgba(255,255,255,0.9),-2px -2px 6px rgba(255,255,255,0.5);
}
input:focus,
select:focus{
    border-color:#2f6dff;
    box-shadow:6px 6px 14px rgba(47,109,255,0.15),inset 4px 4px 8px rgba(255,255,255,0.9),0 0 0 4px rgba(47,109,255,0.15);
}
input::placeholder{color:#95a4bf;font-weight:500;}
.btn-blue,.btn-red{
    padding:10px 16px;
    color:#fff;
    border:none;
    border-radius:var(--radius-md);
    font-size:13px;
    font-weight:700;
    font-family:'Nunito',sans-serif;
    cursor:pointer;
    transition:0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    position:relative;
    overflow:hidden;
}
.btn-blue{
    background:linear-gradient(145deg,var(--primary),var(--primary-light));
    box-shadow:9px 9px 20px rgba(47,109,255,0.26),-7px -7px 16px rgba(255,255,255,0.92);
    margin-top:12px;
}
.btn-blue:before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition:left 0.5s ease;
}
.btn-blue:hover{
    transform:translateY(-2px);
    box-shadow:11px 11px 24px rgba(47,109,255,0.3),-9px -9px 20px rgba(255,255,255,0.93);
}
.btn-blue:hover:before{left:100%;}
.btn-blue:active{transform:translateY(0);box-shadow:5px 5px 10px rgba(47,109,255,0.2),-3px -3px 8px rgba(255,255,255,0.9);}
.btn-red{
    background:linear-gradient(145deg,#e65f75,#ef8798);
    box-shadow:8px 8px 16px rgba(230,95,117,0.28),-6px -6px 14px rgba(255,255,255,0.92);
    padding:8px 12px;
    font-size:12px;
}
.btn-red:hover{
    transform:translateY(-2px);
    box-shadow:10px 10px 20px rgba(230,95,117,0.32),-8px -8px 18px rgba(255,255,255,0.92);
}
.table-wrap{overflow-x:auto;border-radius:var(--radius-md);}
table{width:100%;border-collapse:collapse;font-size:13px;min-width:760px;}
th,td{padding:13px 14px;text-align:left;vertical-align:middle;}
th{font-size:11px;text-transform:uppercase;letter-spacing:0.08em;color:#587097;background:linear-gradient(135deg, #dbe3f3 0%, #eef1f8 100%);font-weight:700;border-bottom:2px solid #c5cfe0;}
td{color:#314767;border-bottom:1px solid #d2daea;}
tr:hover td{background:linear-gradient(90deg, #edf2fb, #f3f6fc);transition:0.2s ease;}
.badge{
    padding:5px 12px;
    border-radius:999px;
    font-size:11px;
    font-weight:800;
    display:inline-block;
    letter-spacing:0.05em;
}
.badge-Pending{color:#8a5b06;background:#f9e8bf;box-shadow:0 4px 8px rgba(212,164,46,0.2);}
.badge-In-Progress{color:#204f95;background:#cee0ff;box-shadow:0 4px 8px rgba(47,109,255,0.2);}
.badge-Done{color:#1c6d47;background:#caefd9;box-shadow:0 4px 8px rgba(44,189,118,0.2);}
.reminder-card{
    margin-bottom:12px;
    padding:14px 16px;
    border-radius:var(--radius-md);
    background:linear-gradient(135deg, #fae6ea 0%, #faf0f3 100%);
    color:#7f3b4f;
    border:1px solid #f2bac4;
    box-shadow:6px 6px 14px rgba(230,95,117,0.12),inset 2px 2px 4px rgba(255,255,255,0.8);
    transition:0.3s ease;
    font-weight:600;
}
.reminder-card:hover{
    transform:translateX(4px);
    box-shadow:8px 8px 18px rgba(230,95,117,0.16),inset 2px 2px 4px rgba(255,255,255,0.8);
}
.reminder-card.soon{
    background:linear-gradient(135deg, #f9f0d8 0%, #faf6ef 100%);
    border-color:#f1d7a2;
    color:#745719;
    box-shadow:6px 6px 14px rgba(212,164,46,0.12),inset 2px 2px 4px rgba(255,255,255,0.8);
}
.empty{color:var(--muted);font-weight:700;text-align:center;padding:20px;font-size:14px;}
@media (max-width:900px){
    body{padding:14px;}
    .header{flex-direction:column;align-items:flex-start;}
    .form-grid{grid-template-columns:1fr;}
    .stats{grid-template-columns:repeat(auto-fit,minmax(160px,1fr));}
}
</style>
</head>
<body>
<div class="page-wrap">
    <div class="header">
        <div>
            <h1><i class="bi bi-car-front-fill"></i> Owner Dashboard</h1>
            <p class="header-meta">Manage your vehicles, service history, and reminders.</p>
        </div>
        <a href="../logout.php" class="logout"><i class="bi bi-box-arrow-right"></i> Logout</a>
    </div>

    <div class="stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-truck-front-fill"></i></div>
            <div>
                <h3><?php echo count($vehicles); ?></h3>
                <p>My Vehicles</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-tools"></i></div>
            <div>
                <h3><?php echo count($records); ?></h3>
                <p>Service Records</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-currency-rupee"></i></div>
            <div>
                <h3><?php echo number_format(array_sum(array_column($records, 'cost')), 2); ?></h3>
                <p>Total Spent</p>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon"><i class="bi bi-bell-fill"></i></div>
            <div>
                <h3><?php echo count($reminders); ?></h3>
                <p>Reminders</p>
            </div>
        </div>
    </div>

    <div class="glass-card">
        <p class="section-title"><i class="bi bi-plus-circle-fill"></i> Add New Vehicle</p>
        <form method="POST">
            <div class="form-grid">
                <input type="text" name="make" placeholder="Make (e.g. Toyota)" required>
                <input type="text" name="model" placeholder="Model (e.g. Innova)" required>
                <input type="number" name="year" placeholder="Year (e.g. 2022)" min="1990" max="<?php echo date('Y') + 1; ?>" required>
                <input type="text" name="plate_no" placeholder="Plate No (e.g. MH12AB1234)" required>
            </div>
            <button type="submit" name="add_vehicle" class="btn btn-blue"><i class="bi bi-plus-lg"></i> Add Vehicle</button>
        </form>
    </div>

    <div class="glass-card">
        <p class="section-title"><i class="bi bi-list-stars"></i> My Vehicles</p>
        <?php if (count($vehicles) === 0): ?>
            <p class="empty">No vehicles added yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Make</th><th>Model</th><th>Year</th><th>Plate No</th><th>Action</th></tr>
                <?php foreach ($vehicles as $v): ?>
                <tr>
                    <td><?php echo htmlspecialchars($v['make']); ?></td>
                    <td><?php echo htmlspecialchars($v['model']); ?></td>
                    <td><?php echo $v['year']; ?></td>
                    <td><?php echo htmlspecialchars($v['plate_no']); ?></td>
                    <td>
                        <a href="owner.php?delete_vehicle=<?php echo $v['id']; ?>" class="btn btn-red" onclick="return confirm('Delete this vehicle?')">Delete</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <p class="section-title"><i class="bi bi-wrench-adjustable-circle-fill"></i> Service History</p>
        <?php if (count($records) === 0): ?>
            <p class="empty">No service records yet.</p>
        <?php else: ?>
        <div class="table-wrap">
            <table>
                <tr><th>Vehicle</th><th>Plate</th><th>Mechanic</th><th>Service</th><th>Cost</th><th>Mileage</th><th>Status</th><th>Date</th></tr>
                <?php foreach ($records as $r): ?>
                <tr>
                    <td><?php echo htmlspecialchars($r['make'] . ' ' . $r['model']); ?></td>
                    <td><?php echo htmlspecialchars($r['plate_no']); ?></td>
                    <td><?php echo htmlspecialchars($r['mechanic_name']); ?></td>
                    <td><?php echo htmlspecialchars($r['service_type']); ?></td>
                    <td><i class="bi bi-currency-rupee"></i><?php echo number_format($r['cost'], 2); ?></td>
                    <td><?php echo number_format($r['mileage_km']); ?> km</td>
                    <td>
                        <span class="badge badge-<?php echo str_replace(' ', '-', $r['status']); ?>">
                            <?php echo htmlspecialchars($r['status']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($r['service_date']); ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <div class="glass-card">
        <p class="section-title"><i class="bi bi-bell-fill"></i> Upcoming Reminders</p>
        <?php if (count($reminders) === 0): ?>
            <p class="empty">No reminders yet.</p>
        <?php else: ?>
            <?php foreach ($reminders as $rem):
                $daysLeft = (strtotime($rem['due_date']) - time()) / 86400;
                $cls = $daysLeft < 7 ? '' : 'soon';
            ?>
            <div class="reminder-card <?php echo $cls; ?>">
                <strong><?php echo htmlspecialchars($rem['make'] . ' ' . $rem['model']); ?></strong>
                (<?php echo htmlspecialchars($rem['plate_no']); ?>) -
                <?php echo htmlspecialchars($rem['reminder_type']); ?> due on
                <strong><?php echo htmlspecialchars($rem['due_date']); ?></strong>
                (<?php echo round($daysLeft); ?> days left)
            </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
</body>
</html>

