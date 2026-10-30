<?php
require_once '../includes/auth.php';
requireRole(['Doctor']);
$stmt = $pdo->prepare("SELECT * FROM patients WHERE assigned_doctor_id = ?"); $stmt->execute([$_SESSION['user_id']]); $patients = $stmt->fetchAll();
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4"><div class="card-header-custom"><h4>My Patients</h4></div><div class="card-body">
    <table class="table">
        <?php foreach($patients as $p): ?>
        <tr><td><?= e($p['patient_id']) ?><br><?= e($p['full_name']) ?></td><td><a href="epr.php?pid=<?= e($p['patient_id']) ?>" class="btn btn-sm btn-primary">View EPR</a></td></tr>
        <?php endforeach; ?>
    </table>
</div></div>
</body></html> Thei si my firat co