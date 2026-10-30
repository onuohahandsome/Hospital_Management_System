<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Pharmacist']);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['dispense_id'])) {
    $rx_id = (int)$_POST['dispense_id'];
    $stmt = $pdo->prepare("UPDATE prescriptions SET status = 'Dispensed', dispensed_by = ?, dispensed_at = NOW() WHERE prescription_id = ? AND status = 'Issued'");
    $stmt->execute([$_SESSION['user_id'], $rx_id]);
    if ($stmt->rowCount() > 0) {
        $p = $pdo->prepare("SELECT c.patient_id FROM prescriptions p JOIN consultations c ON p.consultation_id = c.consultation_id WHERE p.prescription_id = ?"); $p->execute([$rx_id]); $pid = $p->fetchColumn();
        if ($pid) { $pdo->prepare("UPDATE visits SET visit_status = 'Completed' WHERE patient_id = ? AND visit_status = 'Awaiting_Pharmacy' AND DATE(check_in_time) = CURDATE()")->execute([$pid]); logAction($pid, 'Dispense'); }
        header("Location: queue.php?success=dispensed&rx=" . $rx_id); ob_end_flush(); exit();
    }
}
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['revision_id'])) {
    $rx_id = (int)$_POST['revision_id']; $note = trim($_POST['revision_note']);
    $stmt = $pdo->prepare("UPDATE prescriptions SET status = 'Revision_Requested', revision_note = ? WHERE prescription_id = ? AND status = 'Issued'");
    $stmt->execute([$note, $rx_id]);
    if ($stmt->rowCount() > 0) { logAction(null, 'Request_Revision'); header("Location: queue.php?success=revision"); ob_end_flush(); exit(); }
}
$pending = $pdo->query("SELECT p.*, pat.full_name, pat.patient_id, c.is_emergency, (SELECT allergies FROM vitals v WHERE v.patient_id = pat.patient_id ORDER BY v.date_time DESC LIMIT 1) AS allergies FROM prescriptions p JOIN consultations c ON p.consultation_id = c.consultation_id JOIN patients pat ON c.patient_id = pat.patient_id WHERE p.status = 'Issued' ORDER BY c.is_emergency DESC, p.date_time ASC")->fetchAll();
$dispensed = $pdo->query("SELECT p.*, pat.full_name, u.full_name as dispensed_by_name FROM prescriptions p JOIN consultations c ON p.consultation_id = c.consultation_id JOIN patients pat ON c.patient_id = pat.patient_id LEFT JOIN users u ON p.dispensed_by = u.id WHERE p.status = 'Dispensed' AND DATE(p.dispensed_at) = CURDATE() ORDER BY p.dispensed_at DESC")->fetchAll();
require_once '../includes/header.php';
?>
<?php if (isset($_GET['success'])) echo "<div class='alert alert-success'>Action completed.</div>"; ?>
<div class="card card-custom mt-3"><div class="card-header-custom"><h4>Pending Queue</h4></div><div class="card-body p-0">
    <table class="table">
        <thead><tr><th>Rx</th><th>Patient</th><th>Medication</th><th>Action</th></tr></thead>
        <tbody>
            <?php foreach($pending as $rx): ?>
            <tr>
                <td>RX-<?= e($rx['prescription_id']) ?></td>
                <td><?= e($rx['full_name']) ?> <?= $rx['allergies'] ? "<br><small class='text-danger'>Allergy: ".e($rx['allergies'])."</small>" : '' ?></td>
                <td style="white-space:pre-line;"><?= e($rx['medication_details']) ?></td>
                <td>
                    <a href="prescription_slip.php?id=<?= e($rx['prescription_id']) ?>" target="_blank" class="btn btn-sm btn-info">View Slip</a>
                    <form method="POST" class="d-inline"><input type="hidden" name="dispense_id" value="<?= e($rx['prescription_id']) ?>"><button type="submit" class="btn btn-sm btn-success">Dispense</button></form>
                    <form method="POST" class="d-inline"><input type="hidden" name="revision_id" value="<?= e($rx['prescription_id']) ?>"><input type="text" name="revision_note" required><button type="submit" class="btn btn-sm btn-warning">Revise</button></form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div></div>

<div class="card card-custom mt-4"><div class="card-header-custom d-flex justify-content-between"><h4>Dispensed Today</h4><a href="history.php" class="btn btn-sm btn-outline-success">Full History</a></div><div class="card-body p-0">
    <table class="table">
        <?php foreach($dispensed as $rx): ?>
        <tr><td>RX-<?= e($rx['prescription_id']) ?></td><td><?= e($rx['full_name']) ?></td><td><?= date('h:i A', strtotime($rx['dispensed_at'])) ?></td><td><a href="prescription_slip.php?id=<?= e($rx['prescription_id']) ?>" target="_blank" class="btn btn-sm btn-outline-success">Print</a></td></tr>
        <?php endforeach; ?>
    </table>
</div></div>
</body></html>
<?php ob_end_flush(); ?>