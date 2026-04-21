<?php
session_start();
require 'includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];

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
<html>
<head>
    <title>SmartGarage Login</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #0f172a, #1e293b, #0f3460);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: Arial, sans-serif;
        }
        .glass-card {
            background: rgba(255,255,255,0.05);
            backdrop-filter: blur(16px);
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 16px;
            padding: 40px;
            width: 380px;
            color: #e2e8f0;
        }
        h2 { text-align: center; margin-bottom: 24px; font-size: 24px; }
        input {
            width: 100%;
            padding: 12px;
            margin: 8px 0 16px;
            background: rgba(255,255,255,0.08);
            border: 1px solid rgba(255,255,255,0.15);
            border-radius: 8px;
            color: #e2e8f0;
            font-size: 14px;
        }
        label { font-size: 13px; color: #94a3b8; }
        button {
            width: 100%;
            padding: 12px;
            background: #3b82f6;
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            margin-top: 8px;
        }
        button:hover { background: #2563eb; }
        .error {
            background: rgba(239,68,68,0.2);
            border: 1px solid rgba(239,68,68,0.4);
            color: #fca5a5;
            padding: 10px;
            border-radius: 8px;
            margin-bottom: 16px;
            text-align: center;
            font-size: 13px;
        }
        .logo { text-align: center; font-size: 36px; margin-bottom: 8px; }
    </style>
</head>
<body>
<div class="glass-card">
    <div class="logo">🚗</div>
    <h2>SmartGarage</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo $error; ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Email</label>
        <input type="email" name="email" required>
        <label>Password</label>
        <input type="password" name="password" required>
        <button type="submit">Login</button>
    </form>
</div>
</body>
</html>
