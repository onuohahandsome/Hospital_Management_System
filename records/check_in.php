<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Records']);

$search = $_GET['search'] ?? ''; $selected_patient = null; $message = "";

if (isset($_GET['pid'])) {
    $stmt = $pdo->prepare("SELECT p.*, u.full_name as doctor_name FROM patients p LEFT JOIN users u ON p.assigned_doctor_id = u.id WHERE p.patient_id = ?");
    $stmt->execute([$_GET['pid']]); $selected_patient = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirm_check_in'])) {
    $pid = $_POST['patient_id']; $doctor_id = (int)$_POST['assigned_doctor']; $is_emergency = isset($_POST['is_emergency']) ? 1 : 0;
    $check = $pdo->prepare("SELECT visit_id FROM visits WHERE patient_id = ? AND DATE(check_in_time) = CURDATE() AND visit_status != 'Completed'"); $check->execute([$pid]);

    if ($check->fetch()) { $message = "<div class='alert alert-danger'>Patient already checked in today.</div>"; }
    else {
        $pdo->prepare("INSERT INTO visits (patient_id, assigned_doctor_id, checked_in_by, is_emergency) VALUES (?, ?, ?, ?)")->execute([$pid, $doctor_id, $_SESSION['user_id'], $is_emergency]);
        $pdo->prepare("UPDATE patients SET assigned_doctor_id = ? WHERE patient_id = ?")->execute([$doctor_id, $pid]);
        logAction($pid, 'Check_In'); $message = "<div class='alert alert-success'>Checked In!</div>"; $selected_patient = null;
    }
}
$doctors = $pdo->query("SELECT id, full_name, department FROM users WHERE role = 'Doctor'")->fetchAll();
$results = [];
if ($search && !$selected_patient) { $stmt = $pdo->prepare("SELECT p.*, u.full_name as doctor_name FROM patients p LEFT JOIN users u ON p.assigned_doctor_id = u.id WHERE p.full_name LIKE ? OR p.patient_id LIKE ?"); $stmt->execute(["%$search%", "%$search%"]); $results = $stmt->fetchAll(); }
require_once '../includes/header.php';
?>
<?= $message ?>
<?php if ($selected_patient): ?>
    <div class="card card-custom mt-4 border-success border-3">
        <div class="card-header-custom bg-success text-white"><h4>Check-In: <?= e($selected_patient['full_name']) ?></h4></div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Reg No.</th><td><?= e($selected_patient['patient_id']) ?></td></tr>
                <tr><th>Name</th><td><?= e($selected_patient['full_name']) ?></td></tr>
            </table>
            <form method="POST" class="mt-4">
                <input type="hidden" name="patient_id" value="<?= e($selected_patient['patient_id']) ?>">
                <div class="mb-3"><label>Assign Doctor</label><select name="assigned_doctor" class="form-select" required><?php foreach($doctors as $d): ?><option value="<?= e($d['id']) ?>" <?= ($d['id'] == $selected_patient['assigned_doctor_id']) ? 'selected' : '' ?>><?= e($d['full_name']) ?></option><?php endforeach; ?></select></div>
                <div class="form-check mb-3"><input type="checkbox" name="is_emergency" class="form-check-input"><label class="form-check-label">Emergency Visit</label></div>
                <button type="submit" name="confirm_check_in" class="btn btn-success w-100">Confirm Check-In</button>
            </form>
        </div>
    </div>
<?php else: ?>
    <div class="card card-custom mt-4">
        <div class="card-header-custom"><h4>Check-In Returning Patient</h4></div>
        <div class="card-body">
            <form method="GET" class="mb-4 d-flex gap-2"><input type="text" name="search" class="form-control" placeholder="Name or Reg No" value="<?= e($search) ?>"><button type="submit" class="btn btn-dark">Search</button></form>
            <?php if (!empty($results)): ?>
                <table class="table">
                    <?php foreach($results as $r): ?>
                    <tr><td><?= e($r['full_name']) ?><br><small><?= e($r['patient_id']) ?></small></td><td><a href="?pid=<?= e($r['patient_id']) ?>" class="btn btn-sm btn-success">Check-In</a> <a href="edit_patient.php?pid=<?= e($r['patient_id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a></td></tr>
                    <?php endforeach; ?>
                </table>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
</body></html>