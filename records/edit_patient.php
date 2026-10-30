<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Records']);
$pid = $_GET['pid'] ?? null; if (!$pid) die("ID required.");
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?"); $stmt->execute([$pid]); $patient = $stmt->fetch(); if (!$patient) die("Not found.");
$error = ""; $success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name']); $phone = trim($_POST['phone']); $assigned_doctor = (int)$_POST['assigned_doctor'];
    try {
        $pdo->prepare("UPDATE patients SET full_name=?, phone=?, assigned_doctor_id=? WHERE patient_id=?")->execute([$full_name, $phone, $assigned_doctor ?: null, $pid]);
        if ($assigned_doctor > 0) { $pdo->prepare("UPDATE visits SET assigned_doctor_id = ? WHERE patient_id = ? AND visit_status != 'Completed' AND DATE(check_in_time) = CURDATE()")->execute([$assigned_doctor, $pid]); }
        logAction($pid, 'Edit_Patient'); $success = "Updated successfully.";
    } catch (Exception $e) { $error = "Update failed."; }
}
$doctors = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Doctor'")->fetchAll();
require_once '../includes/header.php';
?>
<div class="row justify-content-center mt-4"><div class="col-md-8">
    <div class="card card-custom"><div class="card-header-custom"><h5>Edit Patient: <?= e($patient['full_name']) ?></h5></div><div class="card-body">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <form method="POST">
            <div class="mb-3"><label>Full Name</label><input type="text" name="full_name" class="form-control" value="<?= e($patient['full_name']) ?>" required></div>
            <div class="mb-3"><label>Phone</label><input type="text" name="phone" class="form-control" value="<?= e($patient['phone']) ?>"></div>
            <div class="mb-3"><label>Doctor</label><select name="assigned_doctor" class="form-select"><option value="">None</option><?php foreach ($doctors as $d): ?><option value="<?= e($d['id']) ?>" <?= $patient['assigned_doctor_id'] == $d['id'] ? 'selected' : '' ?>><?= e($d['full_name']) ?></option><?php endforeach; ?></select></div>
            <button type="submit" class="btn btn-primary">Save</button> <a href="register.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div></div>
</div></div>
</body></html>