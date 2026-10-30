<?php
ob_start();
session_start();
require_once 'config/db.php';
require_once 'config/app_config.php';

if (isset($_SESSION['user_id'])) { header("Location: dashboard.php"); ob_end_flush(); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['department'] = $user['department'];
        $_SESSION['full_name'] = $user['full_name'];
        $stmt = $pdo->prepare("INSERT INTO audit_log (user_id, action) VALUES (?, 'Login')");
        $stmt->execute([$user['id']]);
        header("Location: dashboard.php"); ob_end_flush(); exit();
    } else { $error = "Invalid Staff Credentials!"; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - <?= HOSPITAL_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body style="margin:0; padding:0; background:linear-gradient(135deg, #1e293b 0%, #0f172a 100%); font-family:'Segoe UI',sans-serif;">
    <div style="position:fixed; inset:0; display:flex; align-items:center; justify-content:center; padding:20px; box-sizing:border-box; z-index:9999;">
        <div style="width:100%; max-width:420px; background:#fff; border-radius:15px; box-shadow:0 20px 60px rgba(0,0,0,0.4); overflow:hidden;">
            <div style="background:linear-gradient(135deg, #1e293b 0%, #334155 100%); padding:32px 20px; text-align:center;">
                <i class="fas fa-hospital-symbol" style="font-size:2.8rem; color:#38bdf8; margin-bottom:12px; display:block;"></i>
                <h4 style="color:#fff; font-weight:700; margin:0 0 6px 0;"><?= HOSPITAL_NAME ?></h4>
                <small style="color:#cbd5e1; font-size:0.85rem;">Staff Authentication Portal</small>
            </div>
            <div style="padding:35px 30px;">
                <?php if(isset($error)): ?><div class="alert alert-danger py-2 mb-3"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error) ?></div><?php endif; ?>
                <form method="POST">
                    <div style="margin-bottom:16px;">
                        <label style="display:block; font-weight:600; font-size:0.9rem; margin-bottom:6px; color:#334155;"><i class="fas fa-user"></i> Staff ID</label>
                        <input type="text" name="username" style="width:100%; padding:11px 14px; border:1px solid #dee2e6; border-radius:8px; box-sizing:border-box;" required autofocus placeholder="Enter staff ID">
                    </div>
                    <div style="margin-bottom:22px;">
                        <label style="display:block; font-weight:600; font-size:0.9rem; margin-bottom:6px; color:#334155;"><i class="fas fa-lock"></i> Password</label>
                        <input type="password" name="password" style="width:100%; padding:11px 14px; border:1px solid #dee2e6; border-radius:8px; box-sizing:border-box;" required placeholder="Enter password">
                    </div>
                    <button type="submit" style="width:100%; padding:12px; background:#1a73e8; color:white; border:none; border-radius:8px; font-weight:600; cursor:pointer;"><i class="fas fa-sign-in-alt"></i> Login to System</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
<?php ob_end_flush(); ?>