<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Admin']);
$id = (int)$_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?"); $stmt->execute([$id]); $user = $stmt->fetch(); if (!$user) die("Not found.");
$error = ""; $success = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($_POST['action'] === 'update') {
        $pdo->prepare("UPDATE users SET full_name=?, email=?, role=?, department=? WHERE id=?")->execute([$_POST['full_name'], $_POST['email'], $_POST['role'], $_POST['department'], $id]);
        $success = "Updated."; $stmt->execute([$id]); $user = $stmt->fetch();
    } elseif ($_POST['action'] === 'reset_password') {
        $hash = password_hash($_POST['new_password'], PASSWORD_DEFAULT);
        $pdo->prepare("UPDATE users SET password_hash=? WHERE id=?")->execute([$hash, $id]);
        $success = "Password reset.";
    }
}
require_once '../includes/header.php';
?>
<div class="row justify-content-center mt-4"><div class="col-md-6"><div class="card card-custom"><div class="card-header-custom"><h5>Edit Staff</h5></div><div class="card-body">
    <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
    <form method="POST" class="mb-4">
        <input type="hidden" name="action" value="update">
        <div class="mb-3"><label>Name</label><input type="text" name="full_name" class="form-control" value="<?= e($user['full_name']) ?>" required></div>
        <div class="mb-3"><label>Email</label><input type="email" name="email" class="form-control" value="<?= e($user['email']) ?>" required></div>
        <div class="row mb-3">
            <div class="col-6"><label>Role</label><select name="role" class="form-select"><option <?= $user['role']=='Doctor'?'selected':'' ?>>Doctor</option><option <?= $user['role']=='Nurse'?'selected':'' ?>>Nurse</option><option <?= $user['role']=='Pharmacist'?'selected':'' ?>>Pharmacist</option><option <?= $user['role']=='Records'?'selected':'' ?>>Records</option><option <?= $user['role']=='Admin'?'selected':'' ?>>Admin</option></select></div>
            <div class="col-6"><label>Dept</label><select name="department" class="form-select"><option value="OPD" <?= $user['department']=='OPD'?'selected':'' ?>>OPD</option><option value="Emergency" <?= $user['department']=='Emergency'?'selected':'' ?>>Emergency</option></select></div>
        </div>
        <button type="submit" class="btn btn-primary">Update Details</button>
    </form>
    <hr>
    <form method="POST">
        <input type="hidden" name="action" value="reset_password">
        <div class="mb-3"><label>New Password</label><input type="password" name="new_password" class="form-control" required minlength="6"></div>
        <button type="submit" class="btn btn-warning">Reset Password</button>
    </form>
</div></div></div></div>
</body></html>