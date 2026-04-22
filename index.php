<?php
session_start();
require 'includes/db.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        $pdo->prepare("INSERT INTO activity_log (user_id, action) VALUES (?,?)")->execute([$user['id'], 'Logged in']);
        if ($user['role'] === 'owner') header("Location: portals/owner.php");
        elseif ($user['role'] === 'mechanic') header("Location: portals/mechanic.php");
        elseif ($user['role'] === 'admin') header("Location: portals/admin.php");
        exit;
    } else {
        $error = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartGarage — Login</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
<style>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap');
*{margin:0;padding:0;box-sizing:border-box;}
body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#0f172a 0%,#1e293b 50%,#0f3460 100%);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;}
.container{display:flex;width:100%;max-width:900px;min-height:520px;border-radius:24px;overflow:hidden;box-shadow:0 25px 60px rgba(0,0,0,0.5);}
.left-panel{flex:1;background:linear-gradient(145deg,rgba(59,130,246,0.3),rgba(15,52,96,0.8));backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.1);padding:50px 40px;display:flex;flex-direction:column;justify-content:center;color:#e2e8f0;}
.brand{display:flex;align-items:center;gap:12px;margin-bottom:24px;}
.brand-icon{width:52px;height:52px;background:linear-gradient(135deg,#3b82f6,#1d4ed8);border-radius:14px;display:flex;align-items:center;justify-content:center;}
.brand-icon i{font-size:26px;color:white;}
.brand h1{font-size:28px;font-weight:700;}
.left-panel p{font-size:14px;color:#94a3b8;line-height:1.7;margin-bottom:28px;}
.feature{display:flex;align-items:center;gap:10px;margin-bottom:12px;font-size:13px;color:#cbd5e1;}
.feature i{color:#3b82f6;font-size:16px;}
.role-badges{display:flex;gap:8px;margin-top:20px;flex-wrap:wrap;}
.role-badge{padding:4px 12px;border-radius:20px;font-size:11px;font-weight:600;border:1px solid;display:flex;align-items:center;gap:5px;}
.rb-owner{background:rgba(168,85,247,0.15);color:#c084fc;border-color:rgba(168,85,247,0.3);}
.rb-mechanic{background:rgba(59,130,246,0.15);color:#60a5fa;border-color:rgba(59,130,246,0.3);}
.rb-admin{background:rgba(239,68,68,0.15);color:#f87171;border-color:rgba(239,68,68,0.3);}
.right-panel{flex:1;background:rgba(255,255,255,0.04);backdrop-filter:blur(20px);border:1px solid rgba(255,255,255,0.08);padding:50px 40px;display:flex;flex-direction:column;justify-content:center;}
h2{font-size:24px;font-weight:700;color:#f1f5f9;margin-bottom:6px;}
.subtitle{font-size:13px;color:#64748b;margin-bottom:28px;}
.form-group{margin-bottom:18px;position:relative;}
label{display:block;font-size:11px;font-weight:600;color:#94a3b8;margin-bottom:6px;letter-spacing:0.5px;text-transform:uppercase;}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#475569;font-size:16px;}
input{width:100%;padding:12px 16px 12px 42px;background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:10px;color:#f1f5f9;font-size:14px;font-family:'Inter',sans-serif;transition:all 0.3s;outline:none;}
input:focus{border-color:#3b82f6;background:rgba(59,130,246,0.08);box-shadow:0 0 0 3px rgba(59,130,246,0.15);}
input::placeholder{color:#475569;}
.btn-login{width:100%;padding:13px;background:linear-gradient(135deg,#3b82f6,#2563eb);color:white;border:none;border-radius:10px;font-size:15px;font-weight:600;cursor:pointer;font-family:'Inter',sans-serif;transition:all 0.3s;margin-top:6px;display:flex;align-items:center;justify-content:center;gap:8px;}
.btn-login:hover{background:linear-gradient(135deg,#2563eb,#1d4ed8);transform:translateY(-1px);box-shadow:0 8px 20px rgba(59,130,246,0.4);}
.error{background:rgba(239,68,68,0.1);border:1px solid rgba(239,68,68,0.3);color:#fca5a5;padding:12px 16px;border-radius:10px;font-size:13px;margin-bottom:18px;display:flex;align-items:center;gap:8px;}
.divider{text-align:center;color:#475569;font-size:12px;margin:20px 0;position:relative;}
.divider::before,.divider::after{content:'';position:absolute;top:50%;width:42%;height:1px;background:rgba(255,255,255,0.08);}
.divider::before{left:0;}.divider::after{right:0;}
.signup-link{text-align:center;font-size:13px;color:#64748b;}
.signup-link a{color:#3b82f6;text-decoration:none;font-weight:600;}
.signup-link a:hover{text-decoration:underline;}
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-car-front-fill"></i></div>
            <h1>SmartGarage</h1>
        </div>
        <p>Professional car service management platform for owners, mechanics, and administrators.</p>
        <div class="feature"><i class="bi bi-clock-history"></i> Track full service history</div>
        <div class="feature"><i class="bi bi-bell-fill"></i> Auto service reminders</div>
        <div class="feature"><i class="bi bi-arrow-repeat"></i> Real-time job status updates</div>
        <div class="feature"><i class="bi bi-bar-chart-fill"></i> Analytics and CSV export</div>
        <div class="role-badges">
            <span class="role-badge rb-owner"><i class="bi bi-person-fill"></i> OWNER</span>
            <span class="role-badge rb-mechanic"><i class="bi bi-wrench-adjustable"></i> MECHANIC</span>
            <span class="role-badge rb-admin"><i class="bi bi-shield-fill"></i> ADMIN</span>
        </div>
    </div>
    <div class="right-panel">
        <h2>Welcome back</h2>
        <p class="subtitle">Sign in to your SmartGarage account</p>
        <?php if ($error): ?>
        <div class="error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo $error; ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="form-group">
                <label>Email Address</label>
                <div class="input-wrap">
                    <i class="bi bi-envelope-fill"></i>
                    <input type="email" name="email" placeholder="you@example.com" required>
                </div>
            </div>
            <div class="form-group">
                <label>Password</label>
                <div class="input-wrap">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="password" placeholder="Enter your password" required>
                </div>
            </div>
            <button type="submit" class="btn-login">
                <i class="bi bi-box-arrow-in-right"></i> Sign In
            </button>
        </form>
        <div class="divider">or</div>
        <div class="signup-link">
            Don't have an account? <a href="signup.php">Create one here</a>
        </div>
    </div>
</div>
</body>
</html>



