<?php
require_once '../includes/auth.php';
requireRole(['Records']);
$stmt = $pdo->prepare("SELECT v.*, p.full_name, p.category, u.full_name as doctor_name FROM visits v JOIN patients p ON v.patient_id = p.patient_id JOIN users u ON v.assigned_doctor_id = u.id WHERE DATE(v.check_in_time) = CURDATE() ORDER BY v.check_in_time DESC");
$stmt->execute(); $today = $stmt->fetchAll();
require_once '../includes/header.php';
?>
<div class="row mt-4">
    <div class="col-md-6"><a href="register.php" class="text-decoration-none"><div class="card p-4 border-primary h-100 text-center"><h3>New Patient</h3></div></a></div>
    <div class="col-md-6"><a href="check_in.php" class="text-decoration-none"><div class="card p-4 border-success h-100 text-center"><h3>Returning Patient</h3></div></a></div>
</div>
<div class="card card-custom mt-4"><div class="card-header-custom"><h5>Today's Check-Ins</h5></div><div class="card-body">
    <table class="table table-sm">
        <?php foreach($today as $v): ?>
        <tr><td><?= date('h:i A', strtotime($v['check_in_time'])) ?></td><td><?= e($v['patient_id']) ?></td><td><?= e($v['full_name']) ?></td><td><span class="badge bg-primary"><?= str_replace('_', ' ', $v['visit_status']) ?></span></td></tr>
        <?php endforeach; ?>
    </table>
</div></div>
</body></html>