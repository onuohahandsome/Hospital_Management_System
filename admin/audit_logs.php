<?php
require_once '../includes/auth.php';
requireRole(['Admin']);
$logs = $pdo->query("SELECT a.*, u.username, u.role FROM audit_log a JOIN users u ON a.user_id = u.id ORDER BY a.date_time DESC LIMIT 100")->fetchAll();
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4"><div class="card-header-custom"><h4>Audit Logs</h4></div><div class="card-body">
    <table class="table table-sm">
        <?php foreach($logs as $l): ?>
        <tr><td><?= date('d-M-Y H:i', strtotime($l['date_time'])) ?></td><td><?= e($l['username']) ?></td><td><span class="badge bg-info"><?= e($l['role']) ?></span></td><td><?= e($l['action']) ?></td><td><?= e($l['patient_id']) ?></td><td><?= e($l['reason']) ?></td></tr>
        <?php endforeach; ?>
    </table>
</div></div>
</body></html>