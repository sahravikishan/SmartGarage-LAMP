<?php
session_start();
require 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm  = $_POST['confirm'];
    $role     = $_POST['role'];

    if (strlen($name) < 2) {
        $error = 'Name must be at least 2 characters.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = 'This email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (name, email, password, role) VALUES (?,?,?,?)");
            $stmt->execute([$name, $email, $hash, $role]);
            $success = 'Account created successfully! You can now login.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartGarage — Sign Up</title>
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
* { margin:0; padding:0; box-sizing:border-box; }
body {
    font-family:'Inter',sans-serif;
    background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #0f3460 100%);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:20px;
}
.container {
    display:flex;
    width:100%;
    max-width:900px;
    min-height:580px;
    border-radius:24px;
    overflow:hidden;
    box-shadow:0 25px 60px rgba(0,0,0,0.5);
}
.left-panel {
    flex:1;
    background: linear-gradient(145deg, rgba(59,130,246,0.3), rgba(15,52,96,0.8));
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,0.1);
    padding:50px 40px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    color:#e2e8f0;
}
.left-panel .logo { font-size:48px; margin-bottom:16px; }
.left-panel h1 { font-size:32px; font-weight:700; margin-bottom:10px; }
.left-panel p { font-size:14px; color:#94a3b8; line-height:1.7; margin-bottom:24px; }
.role-info { margin-bottom:16px; }
.role-item {
    display:flex;
    align-items:flex-start;
    gap:12px;
    margin-bottom:16px;
    padding:12px;
    background:rgba(255,255,255,0.05);
    border-radius:10px;
    border:1px solid rgba(255,255,255,0.08);
}
.role-icon { font-size:20px; }
.role-item h4 { font-size:13px; font-weight:600; color:#e2e8f0; margin-bottom:2px; }
.role-item p { font-size:11px; color:#94a3b8; line-height:1.4; margin:0; }
.right-panel {
    flex:1;
    background:rgba(255,255,255,0.04);
    backdrop-filter:blur(20px);
    border:1px solid rgba(255,255,255,0.08);
    padding:40px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    overflow-y:auto;
}
h2 { font-size:24px; font-weight:700; color:#f1f5f9; margin-bottom:6px; }
.subtitle { font-size:13px; color:#64748b; margin-bottom:24px; }
.form-group { margin-bottom:16px; }
.form-row { display:grid; grid-template-columns:1fr 1fr; gap:12px; }
label { display:block; font-size:11px; font-weight:500; color:#94a3b8; margin-bottom:6px; letter-spacing:0.5px; text-transform:uppercase; }
input, select {
    width:100%;
    padding:11px 14px;
    background:rgba(255,255,255,0.06);
    border:1px solid rgba(255,255,255,0.12);
    border-radius:10px;
    color:#f1f5f9;
    font-size:13px;
    font-family:'Inter',sans-serif;
    transition:all 0.3s;
    outline:none;
}
select option { background:#1e293b; color:#f1f5f9; }
input:focus, select:focus {
    border-color:#3b82f6;
    background:rgba(59,130,246,0.08);
    box-shadow:0 0 0 3px rgba(59,130,246,0.15);
}
input::placeholder { color:#475569; }
.btn-signup {
    width:100%;
    padding:13px;
    background: linear-gradient(135deg, #3b82f6, #2563eb);
    color:white;
    border:none;
    border-radius:10px;
    font-size:15px;
    font-weight:600;
    cursor:pointer;
    font-family:'Inter',sans-serif;
    transition:all 0.3s;
    margin-top:6px;
}
.btn-signup:hover {
    transform:translateY(-1px);
    box-shadow:0 8px 20px rgba(59,130,246,0.4);
}
.error {
    background:rgba(239,68,68,0.1);
    border:1px solid rgba(239,68,68,0.3);
    color:#fca5a5;
    padding:12px 16px;
    border-radius:10px;
    font-size:13px;
    margin-bottom:16px;
}
.success {
    background:rgba(34,197,94,0.1);
    border:1px solid rgba(34,197,94,0.3);
    color:#86efac;
    padding:12px 16px;
    border-radius:10px;
    font-size:13px;
    margin-bottom:16px;
}
.login-link {
    text-align:center;
    font-size:13px;
    color:#64748b;
    margin-top:16px;
}
.login-link a { color:#3b82f6; text-decoration:none; font-weight:600; }
.login-link a:hover { text-decoration:underline; }
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <div class="logo">🚗</div>
        <h1>Join SmartGarage</h1>
        <p>Create your account and choose your role to get started.</p>
        <div class="role-info">
            <div class="role-item">
                <div class="role-icon">👤</div>
                <div>
                    <h4>Owner</h4>
                    <p>Register vehicles, view service history and reminders</p>
                </div>
            </div>
            <div class="role-item">
                <div class="role-icon">🔧</div>
                <div>
                    <h4>Mechanic</h4>
                    <p>Log service jobs, update status and add notes</p>
                </div>
            </div>
            <div class="role-item">
                <div class="role-icon">⚙️</div>
                <div>
                    <h4>Admin</h4>
                    <p>Manage all users, view analytics and export reports</p>
                </div>
            </div>
        </div>
    </div>
    <div class="right-panel">
        <h2>Create Account</h2>
        <p class="subtitle">Fill in your details to get started</p>
        <?php if ($error): ?>
        <div class="error">⚠️ <?php echo $error; ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="success">✅ <?php echo $success; ?> <a href="index.php" style="color:#4ade80;">Login now →</a></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="name" placeholder="e.g. Ravi Sharma" required>
            </div>
            <div class="form-group">
                <label>Email Address</label>
                <input type="email" name="email" placeholder="you@example.com" required>
            </div>
            <div class="form-group">
                <label>Select Role</label>
                <select name="role" required>
                    <option value="">-- Choose your role --</option>
                    <option value="owner">👤 Owner — Register & track vehicles</option>
                    <option value="mechanic">🔧 Mechanic — Log & update service jobs</option>
                    <option value="admin">⚙️ Admin — Full system access</option>
                </select>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label>Password</label>
                    <input type="password" name="password" placeholder="Min 6 characters" required>
                </div>
                <div class="form-group">
                    <label>Confirm Password</label>
                    <input type="password" name="confirm" placeholder="Repeat password" required>
                </div>
            </div>
            <button type="submit" class="btn-signup">Create Account →</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="index.php">Sign in here</a>
        </div>
    </div>
</div>
</body>
</html>


