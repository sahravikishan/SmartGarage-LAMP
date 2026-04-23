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
        if ($user['role'] === 'owner') {
            header("Location: portals/owner.php");
        } elseif ($user['role'] === 'mechanic') {
            header("Location: portals/mechanic.php");
        } elseif ($user['role'] === 'admin') {
            header("Location: portals/admin.php");
        }
        exit;
    }

    $error = 'Invalid email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>SmartGarage - Login</title>
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
    --danger:#e65f75;
    --danger-light:#ef8798;
    --shadow-dark:#c5cee0;
    --shadow-light:#ffffff;
    --radius-md: 16px;
    --radius-lg: 28px;
}
*{box-sizing:border-box;}
body{
    margin:0;
    font-family:'Nunito',sans-serif;
    color:var(--text);
    background:
        radial-gradient(circle at 15% 10%,#f6f8fd 0%,transparent 42%),
        radial-gradient(circle at 85% 92%,#d3def5 0%,transparent 36%),
        linear-gradient(160deg,#e6ebf4 0%,#dde4f1 100%);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
    padding:24px;
}
.container{
    display:flex;
    width:100%;
    max-width:980px;
    min-height:560px;
    gap:20px;
}
.left-panel,.right-panel{
    background:var(--surface);
    border-radius:var(--radius-lg);
    box-shadow:
        16px 16px 40px var(--shadow-dark),
        -16px -16px 40px var(--shadow-light),
        inset 1px 1px 2px rgba(255,255,255,0.5);
    border:1px solid rgba(255,255,255,0.8);
}
.left-panel{
    flex:1;
    padding:46px 38px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}
.brand{display:flex;align-items:center;gap:14px;margin-bottom:22px;}
.brand-icon{
    width:64px;height:64px;border-radius:var(--radius-md);
    display:flex;align-items:center;justify-content:center;
    background:linear-gradient(145deg,#2f6dff,#5f95ff);
    box-shadow:12px 12px 24px rgba(47,109,255,0.3),-8px -8px 20px rgba(255,255,255,0.95);
    border:1px solid rgba(255,255,255,0.5);
}
.brand-icon i{font-size:32px;color:#fff;}
.brand h1{font-size:32px;font-weight:800;margin:0;background:linear-gradient(135deg, var(--text), #2f6dff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.left-panel p{margin:0 0 26px;font-size:14px;line-height:1.7;color:var(--muted);}
.feature{display:flex;align-items:center;gap:10px;margin-bottom:14px;font-size:13px;font-weight:600;color:#3b4f70;transition:0.3s ease;}
.feature:hover{transform:translateX(4px);color:var(--primary);}
.feature i{color:var(--primary);font-size:16px;}
.role-badges{display:flex;gap:8px;margin-top:20px;flex-wrap:wrap;}
.role-badge{
    padding:8px 14px;border-radius:999px;font-size:11px;font-weight:700;
    display:flex;align-items:center;gap:6px;color:#405375;
    background:var(--bg-soft);
    box-shadow:4px 4px 10px rgba(0,0,0,0.08),-4px -4px 10px rgba(255,255,255,0.95);
    border:1px solid rgba(255,255,255,0.6);
    transition:0.3s ease;
}
.role-badge:hover{transform:translateY(-2px);box-shadow:6px 6px 16px rgba(47,109,255,0.15),-6px -6px 16px rgba(255,255,255,0.95);}
.right-panel{
    flex:1;
    padding:46px 38px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}
h2{font-size:32px;font-weight:800;margin:0;color:var(--text);}
.subtitle{margin:6px 0 24px;font-size:14px;color:var(--muted);line-height:1.6;}
.form-group{margin-bottom:18px;}
label{
    display:block;
    margin-bottom:8px;
    font-size:11px;
    letter-spacing:0.08em;
    text-transform:uppercase;
    color:#6d7e9c;
    font-weight:700;
}
.input-wrap{position:relative;}
.input-wrap i{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:#8a96b3;font-size:16px;transition:0.3s ease;}
input{
    width:100%;
    padding:13px 14px 13px 44px;
    border:1.5px solid rgba(255,255,255,0.8);
    border-radius:var(--radius-md);
    background:var(--bg-soft);
    color:var(--text);
    font-size:14px;
    font-family:'Nunito',sans-serif;
    font-weight:600;
    box-shadow:6px 6px 14px rgba(0,0,0,0.06),inset 4px 4px 8px rgba(255,255,255,0.9),-2px -2px 6px rgba(255,255,255,0.5);
    outline:none;
    transition:0.3s ease;
}
input:hover{
    border-color:#a8c5ff;
    box-shadow:6px 6px 14px rgba(0,0,0,0.08),inset 4px 4px 8px rgba(255,255,255,0.9),-2px -2px 6px rgba(255,255,255,0.5);
}
input:focus{
    border-color:#2f6dff;
    box-shadow:6px 6px 14px rgba(47,109,255,0.15),inset 4px 4px 8px rgba(255,255,255,0.9),0 0 0 4px rgba(47,109,255,0.15);
}
input:focus + i,
input:focus ~ i{
    color:var(--primary);
    transform:translateY(-50%) scale(1.1);
}
input::placeholder{color:#95a4bf;font-weight:500;}
.btn-login{
    width:100%;
    margin-top:12px;
    padding:13px 16px;
    border:none;
    border-radius:var(--radius-md);
    color:#fff;
    font-weight:700;
    font-size:15px;
    font-family:'Nunito',sans-serif;
    background:linear-gradient(145deg,var(--primary),var(--primary-light));
    box-shadow:10px 10px 24px rgba(47,109,255,0.28),-8px -8px 20px rgba(255,255,255,0.9);
    display:flex;align-items:center;justify-content:center;gap:8px;
    cursor:pointer;
    transition:0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    position:relative;
    overflow:hidden;
}
.btn-login:before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition:left 0.5s ease;
}
.btn-login:hover{
    transform:translateY(-3px);
    box-shadow:12px 12px 28px rgba(47,109,255,0.32),-10px -10px 24px rgba(255,255,255,0.92);
}
.btn-login:hover:before{
    left:100%;
}
.btn-login:active{
    transform:translateY(-1px);
    box-shadow:6px 6px 14px rgba(47,109,255,0.2),-4px -4px 12px rgba(255,255,255,0.88);
}
.error{
    margin-bottom:20px;
    padding:13px 16px;
    border-radius:var(--radius-md);
    color:#8d2d3f;
    font-size:13px;
    font-weight:700;
    border:1.5px solid rgba(230,95,117,0.4);
    background:#f7dfe4;
    box-shadow:8px 8px 16px rgba(230,95,117,0.15),inset 2px 2px 4px rgba(255,255,255,0.8);
    display:flex;align-items:center;gap:10px;
    animation:slideIn 0.3s ease;
}
@keyframes slideIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.divider{
    margin:22px 0 20px;
    text-align:center;
    color:#8a99b4;
    font-size:12px;
    font-weight:700;
    position:relative;
}
.divider::before,.divider::after{
    content:'';
    position:absolute;
    top:50%;
    width:42%;
    height:1.5px;
    background:linear-gradient(90deg, transparent, #c5cfe0, transparent);
}
.divider::before{left:0;}
.divider::after{right:0;}
.signup-link{text-align:center;color:var(--muted);font-size:13px;font-weight:600;}
.signup-link a{color:var(--primary);font-weight:800;text-decoration:none;transition:0.2s ease;}
.signup-link a:hover{text-decoration:underline;color:var(--primary-dark);}
@media (max-width:900px){
    .container{flex-direction:column;max-width:620px;}
    .left-panel,.right-panel{padding:34px 28px;}
}
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <div class="brand">
            <div class="brand-icon"><i class="bi bi-car-front-fill"></i></div>
            <h1>SmartGarage</h1>
        </div>
        <p>Professional vehicle service management for owners, mechanics, and administrators.</p>
        <div class="feature"><i class="bi bi-clock-history"></i> Track full service history</div>
        <div class="feature"><i class="bi bi-bell-fill"></i> Auto service reminders</div>
        <div class="feature"><i class="bi bi-arrow-repeat"></i> Real-time job status updates</div>
        <div class="feature"><i class="bi bi-bar-chart-fill"></i> Analytics and CSV export</div>
        <div class="role-badges">
            <span class="role-badge"><i class="bi bi-person-fill"></i> OWNER</span>
            <span class="role-badge"><i class="bi bi-wrench-adjustable"></i> MECHANIC</span>
            <span class="role-badge"><i class="bi bi-shield-fill"></i> ADMIN</span>
        </div>
    </div>
    <div class="right-panel">
        <h2>Welcome Back</h2>
        <p class="subtitle">Sign in to continue to your dashboard</p>
        <?php if ($error): ?>
        <div class="error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?></div>
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
            Do not have an account? <a href="signup.php">Create one here</a>
        </div>
    </div>
</div>
</body>
</html>

