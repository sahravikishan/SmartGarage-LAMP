<?php
session_start();
require 'includes/db.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['confirm'];
    $role = $_POST['role'];

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
<title>SmartGarage - Sign Up</title>
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
    --success:#2cbd76;
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
        radial-gradient(circle at 12% 8%,#f6f8fd 0%,transparent 44%),
        radial-gradient(circle at 88% 90%,#d3def5 0%,transparent 34%),
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
    max-width:1000px;
    min-height:620px;
    gap:24px;
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
    padding:42px 34px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}
.left-panel .logo{
    width:64px;
    height:64px;
    border-radius:var(--radius-md);
    margin-bottom:16px;
    background:linear-gradient(145deg,#2f6dff,#5f95ff);
    color:#fff;
    display:flex;
    align-items:center;
    justify-content:center;
    font-size:32px;
    box-shadow:12px 12px 24px rgba(47,109,255,0.3),-8px -8px 20px rgba(255,255,255,0.95);
    border:1px solid rgba(255,255,255,0.5);
}
.left-panel h1{margin:0 0 12px;font-size:32px;font-weight:800;background:linear-gradient(135deg, var(--text), #2f6dff);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text;}
.left-panel p{margin:0 0 24px;color:var(--muted);line-height:1.7;font-size:14px;}
.role-info{display:flex;flex-direction:column;gap:12px;}
.role-item{
    display:flex;
    gap:12px;
    margin-bottom:8px;
    padding:14px;
    border-radius:var(--radius-md);
    background:var(--bg-soft);
    box-shadow:6px 6px 14px rgba(0,0,0,0.06),-6px -6px 14px rgba(255,255,255,0.95);
    border:1px solid rgba(255,255,255,0.6);
    transition:0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
}
.role-item:hover{
    transform:translateY(-3px);
    box-shadow:10px 10px 20px rgba(47,109,255,0.1),-8px -8px 16px rgba(255,255,255,0.95);
}
.role-icon{
    width:38px;
    height:38px;
    border-radius:12px;
    display:flex;
    align-items:center;
    justify-content:center;
    color:#2f6dff;
    background:#edf2fb;
    box-shadow:inset 3px 3px 8px #cdd5e7,inset -3px -3px 8px #ffffff;
    flex-shrink:0;
}
.role-item h4{margin:0 0 3px;font-size:13px;font-weight:800;color:#344967;}
.role-item p{margin:0;font-size:12px;line-height:1.4;color:#6d7d98;}
.right-panel{
    flex:1;
    padding:40px 34px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}
h2{margin:0;font-size:32px;font-weight:800;color:var(--text);}
.subtitle{margin:6px 0 22px;font-size:14px;color:var(--muted);line-height:1.6;}
.form-group{margin-bottom:16px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
label{
    display:block;
    margin-bottom:8px;
    font-size:11px;
    letter-spacing:0.08em;
    text-transform:uppercase;
    color:#6d7e9c;
    font-weight:700;
}
input,select{
    width:100%;
    padding:13px 14px;
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
select option{color:#24344f;background:#edf2fb;}
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
.btn-signup{
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
    cursor:pointer;
    transition:0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
    position:relative;
    overflow:hidden;
}
.btn-signup:before{
    content:'';
    position:absolute;
    top:0;
    left:-100%;
    width:100%;
    height:100%;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
    transition:left 0.5s ease;
}
.btn-signup:hover{
    transform:translateY(-3px);
    box-shadow:12px 12px 28px rgba(47,109,255,0.32),-10px -10px 24px rgba(255,255,255,0.92);
}
.btn-signup:hover:before{
    left:100%;
}
.btn-signup:active{
    transform:translateY(-1px);
    box-shadow:6px 6px 14px rgba(47,109,255,0.2),-4px -4px 12px rgba(255,255,255,0.88);
}
.error,.success{
    margin-bottom:16px;
    padding:13px 16px;
    border-radius:var(--radius-md);
    font-size:13px;
    font-weight:700;
    display:flex;
    align-items:center;
    gap:10px;
    animation:slideIn 0.3s ease;
}
.error{
    color:#8d2d3f;
    border:1.5px solid rgba(230,95,117,0.4);
    background:#f7dfe4;
    box-shadow:8px 8px 16px rgba(230,95,117,0.15),inset 2px 2px 4px rgba(255,255,255,0.8);
}
.success{
    color:#1d6744;
    border:1.5px solid rgba(44,189,118,0.4);
    background:#ddf5e8;
    box-shadow:8px 8px 16px rgba(44,189,118,0.15),inset 2px 2px 4px rgba(255,255,255,0.8);
}
.success a{color:#0f7d4e;text-decoration:none;font-weight:800;transition:0.2s ease;}
.success a:hover{text-decoration:underline;}
@keyframes slideIn{from{opacity:0;transform:translateY(-10px);}to{opacity:1;transform:translateY(0);}}
.login-link{text-align:center;font-size:13px;color:var(--muted);margin-top:16px;font-weight:600;}
.login-link a{color:var(--primary);font-weight:800;text-decoration:none;transition:0.2s ease;}
.login-link a:hover{text-decoration:underline;color:var(--primary-dark);}
@media (max-width:960px){
    .container{flex-direction:column;max-width:640px;gap:16px;}
    .left-panel,.right-panel{padding:30px 26px;}
    .form-row{grid-template-columns:1fr;}
}
</style>
</head>
<body>
<div class="container">
    <div class="left-panel">
        <div class="logo"><i class="bi bi-car-front-fill"></i></div>
        <h1>Join SmartGarage</h1>
        <p>Create your account and pick your access role to start managing service workflows.</p>
        <div class="role-info">
            <div class="role-item">
                <div class="role-icon"><i class="bi bi-person-fill"></i></div>
                <div>
                    <h4>Owner</h4>
                    <p>Register vehicles, view service history, and track reminders.</p>
                </div>
            </div>
            <div class="role-item">
                <div class="role-icon"><i class="bi bi-wrench-adjustable"></i></div>
                <div>
                    <h4>Mechanic</h4>
                    <p>Log service jobs, update progress, and maintain service notes.</p>
                </div>
            </div>
            <div class="role-item">
                <div class="role-icon"><i class="bi bi-shield-fill"></i></div>
                <div>
                    <h4>Admin</h4>
                    <p>Manage users, monitor metrics, and export service reports.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="right-panel">
        <h2>Create Account</h2>
        <p class="subtitle">Fill in your details to get started</p>
        <?php if ($error): ?>
        <div class="error"><i class="bi bi-exclamation-triangle-fill"></i> <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        <?php if ($success): ?>
        <div class="success"><i class="bi bi-check-circle-fill"></i> <?php echo htmlspecialchars($success); ?> <a href="index.php">Login now</a></div>
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
                    <option value="owner">Owner - Register and track vehicles</option>
                    <option value="mechanic">Mechanic - Log and update service jobs</option>
                    <option value="admin">Admin - Full system access</option>
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

            <button type="submit" class="btn-signup">Create Account</button>
        </form>

        <div class="login-link">
            Already have an account? <a href="index.php">Sign in here</a>
        </div>
    </div>
</div>
</body>
</html>

