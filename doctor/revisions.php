<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Doctor']);
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['rx_id'])) {
    $rx_id = (int)$_POST['rx_id']; $meds = trim($_POST['med_details']);
    $stmt = $pdo->prepare("UPDATE prescriptions SET medication_details = ?, status = 'Issued', revision_note = NULL WHERE prescription_id = ? AND status = 'Revision_Requested'");
    $stmt->execute([$meds, $rx_id]);
    if ($stmt->rowCount() > 0) { logAction(null, 'Revise_Prescription'); header("Location: revisions.php?success=1"); ob_end_flush(); exit(); }
}
$stmt = $pdo->prepare("SELECT p.*, pat.full_name FROM prescriptions p JOIN consultations c ON p.consultation_id = c.consultation_id JOIN patients pat ON c.patient_id = pat.patient_id WHERE p.status = 'Revision_Requested' AND c.doctor_id = ?"); $stmt->execute([$_SESSION['user_id']]); $revisions = $stmt->fetchAll();
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4 border-warning border-2"><div class="card-header-custom bg-warning"><h4>Returned Prescriptions</h4></div><div class="card-body">
    <?php if (isset($_GET['success'])) echo "<div class='alert alert-success'>Revised.</div>"; ?>
    <?php foreach($revisions as $r): ?>
    <div class="card mb-3 p-3">
        <strong>RX-<?= e($r['prescription_id']) ?> - <?= e($r['full_name']) ?></strong>
        <div class="text-danger my-2">Pharmacist Note: <?= e($r['revision_note']) ?></div>
        <form method="POST">
            <input type="hidden" name="rx_id" value="<?= e($r['prescription_id']) ?>">
            <input type="text" name="med_details" class="form-control mb-2" value="<?= e($r['medication_details']) ?>" required>
            <button type="submit" class="btn btn-success btn-sm">Resubmit to Pharmacy</button>
        </form>
    </div>
    <?php endforeach; ?>
</div></div>
</body></html>