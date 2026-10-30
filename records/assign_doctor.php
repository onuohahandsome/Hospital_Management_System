<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Records']);
$message = ""; $search = $_GET['search'] ?? '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_doctor'])) {
    $pid = $_POST['patient_id']; $doctor_id = (int)$_POST['doctor_id'];
    $pdo->prepare("UPDATE patients SET assigned_doctor_id = ? WHERE patient_id = ?")->execute([$doctor_id, $pid]);
    logAction($pid, 'Assign_Doctor'); $message = "<div class='alert alert-success'>Assigned.</div>";
}
$doctors = $pdo->query("SELECT id, full_name FROM users WHERE role = 'Doctor'")->fetchAll();
$stmt = $pdo->query("SELECT p.*, u.full_name as doctor_name FROM patients p LEFT JOIN users u ON p.assigned_doctor_id = u.id ORDER BY p.created_at DESC LIMIT 20"); $patients = $stmt->fetchAll();
require_once '../includes/header.php';
?>
<?= $message ?>
<div class="card card-custom mt-4"><div class="card-header-custom"><h5>Assign Doctor</h5></div><div class="card-body">
    <table class="table">
        <?php foreach($patients as $p): ?>
        <tr>
            <td><?= e($p['patient_id']) ?><br><small><?= e($p['full_name']) ?></small></td>
            <td>
                <form method="POST" class="d-flex gap-2">
                    <input type="hidden" name="patient_id" value="<?= e($p['patient_id']) ?>">
                    <select name="doctor_id" class="form-select form-select-sm" required><option value="">Select...</option><?php foreach($doctors as $d): ?><option value="<?= e($d['id']) ?>" <?= ($d['id'] == $p['assigned_doctor_id']) ? 'selected' : '' ?>><?= e($d['full_name']) ?></option><?php endforeach; ?></select>
                    <button type="submit" name="assign_doctor" class="btn btn-sm btn-primary">Assign</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>
</div></div>
</body></html>