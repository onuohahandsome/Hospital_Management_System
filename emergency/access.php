<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Doctor', 'Nurse']);
if (($_SESSION['department'] ?? '') !== 'Emergency') { die("403 Restricted.</body></html>"); }
$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pid = trim($_POST['patient_id']); $reason = trim($_POST['reason']);
    if (strlen($reason) < 10) { $error = "Reason min 10 chars."; }
    else {
        $stmt = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id = ?"); $stmt->execute([$pid]);
        if (!$stmt->fetch()) { $error = "Patient not found."; }
        else {
            logAction($pid, 'Emergency_Access', $reason);
            $_SESSION['emergency_grants'][$pid] = time() + 3600;
            header("Location: " . BASE_URL . "doctor/epr.php?pid=" . urlencode($pid) . "&emergency=1");
            ob_end_flush(); exit();
        }
    }
}
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4 border-danger"><div class="card-header-custom bg-danger text-white"><h4>Emergency Access</h4></div><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3"><label>Patient ID</label><input type="text" name="patient_id" class="form-control" required></div>
        <div class="mb-3"><label>Reason (min 10 chars)</label><textarea name="reason" class="form-control" required></textarea></div>
        <button type="submit" class="btn btn-danger w-100">Grant Emergency Access</button>
    </form>
</div></div>
</body></html>
<?php ob_end_flush(); ?>