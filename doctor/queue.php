<?php
require_once '../includes/auth.php';
requireRole(['Doctor']);
$stmt = $pdo->prepare("SELECT v.*, p.full_name, vt.blood_pressure, vt.temperature FROM visits v JOIN patients p ON v.patient_id = p.patient_id LEFT JOIN vitals vt ON vt.patient_id = p.patient_id WHERE v.assigned_doctor_id = ? AND v.visit_status = 'Awaiting_Doctor' AND DATE(v.check_in_time) = CURDATE() ORDER BY v.is_emergency DESC, v.check_in_time ASC");
$stmt->execute([$_SESSION['user_id']]); $patients = $stmt->fetchAll();
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4"><div class="card-header-custom"><h4>My Queue</h4></div><div class="card-body">
    <table class="table">
        <thead><tr><th>Reg No</th><th>Name</th><th>BP</th><th>Temp</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach($patients as $p): ?>
            <tr>
                <td><?= e($p['patient_id']) ?> <?= $p['is_emergency'] ? '<span class="badge bg-danger">Emergency</span>' : '' ?></td>
                <td><?= e($p['full_name']) ?></td>
                <td><?= e($p['blood_pressure']) ?></td>
                <td><?= e($p['temperature']) ?>°C</td>
                <td><a href="epr.php?pid=<?= e($p['patient_id']) ?>" class="btn btn-sm btn-primary">Open EPR</a></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div></div>
</body></html>