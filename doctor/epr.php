<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Doctor', 'Nurse']);
$pid = $_GET['pid'] ?? null; if (!$pid) die("No patient.");
$is_emergency = isset($_GET['emergency']) && $_GET['emergency'] == 1;
       
if ($_SESSION['role'] === 'Doctor') {
    $chk = $pdo->prepare("SELECT 1 FROM patients WHERE  patient_id = ? AND assigned_doctor_id = ?"); $chk->execute([$pid, $_SESSION['user_id']]); $is_assigned = $chk->fetchColumn();
    $chk = $pdo->prepare("SELECT 1 FROM visits WHERE patient_id = ? AND assigned_doctor_id = ? AND visit_status IN ('Awaiting_Doctor','Awaiting_Pharmacy') AND DATE(check_in_time) = CURDATE()"); $chk->execute([$pid, $_SESSION['user_id']]); $in_queue = $chk->fetchColumn();
    $has_emergency = isset($_SESSION['emergency_grants'][$pid]) && $_SESSION['emergency_grants'][$pid] > time();
    if (!$is_assigned && !$in_queue && !$has_emergency) { http_response_code(403); die("403 Forbidden.</body></html>"); }
}
logAction($pid, 'View_EPR');
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?"); $stmt->execute([$pid]); $patient = $stmt->fetch();
$stmt = $pdo->prepare("SELECT v.*, u.full_name as nurse_name FROM vitals v JOIN users u ON v.recorded_by = u.id WHERE v.patient_id = ? ORDER BY v.date_time DESC LIMIT 3"); $stmt->execute([$pid]); $vitals = $stmt->fetchAll();
$stmt = $pdo->prepare("SELECT c.*, u.full_name AS doctor_name, p.prescription_id, p.medication_details, p.status AS rx_status, p.revision_note FROM consultations c JOIN users u ON c.doctor_id = u.id LEFT JOIN prescriptions p ON c.consultation_id = p.consultation_id WHERE c.patient_id = ? ORDER BY c.date_time DESC"); $stmt->execute([$pid]); $history = $stmt->fetchAll();
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4">
    <div class="card-header-custom bg-primary text-white">
        <h3>EPR: <?= e($patient['full_name']) ?> (<?= e($patient['patient_id']) ?>)</h3>
        <?php if ($is_emergency): ?><span class="badge bg-danger">EMERGENCY ACCESS</span><?php endif; ?>
    </div>
    <div class="card-body">
        <?php if (!empty($vitals)): $v = $vitals[0]; ?>
            <div class="alert alert-warning"><strong>CC:</strong> <?= e($v['chief_complaint']) ?></div>
            <div class="row mb-3">
                <div class="col-md-3">BP: <strong><?= e($v['blood_pressure']) ?></strong></div>
                <div class="col-md-3">Pulse: <strong><?= e($v['pulse']) ?></strong></div>
                <div class="col-md-3">Temp: <strong><?= e($v['temperature']) ?>°C</strong></div>
                <div class="col-md-3 text-danger">Allergies: <strong><?= e($v['allergies']) ?></strong></div>
            </div>
        <?php endif; ?>
        <h5>Medical History</h5>
        <table class="table">
            <thead><tr><th>Date</th><th>Doctor</th><th>Dx</th><th>Rx</th><th>Status</th></tr></thead>
            <tbody>
                <?php foreach($history as $h): ?>
                <tr>
                    <td><?= date('d-M-Y', strtotime($h['date_time'])) ?></td>
                    <td><?= e($h['doctor_name']) ?></td>
                    <td><?= e($h['diagnosis']) ?></td>
                    <td style="white-space:pre-line;"><?= e($h['medication_details'] ?? 'None') ?><br><?php if ($h['prescription_id']): ?><a href="../pharmacy/prescription_slip.php?id=<?= e($h['prescription_id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary mt-1">View Slip</a><?php endif; ?></td>
                    <td><?= e($h['rx_status'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php if($_SESSION['role'] == 'Doctor'): ?><a href="consult.php?pid=<?= e($pid) ?>" class="btn btn-primary mt-3">Add Consultation</a><?php endif; ?>
    </div>
</div>
</body></html>
<?php ob_end_flush(); ?>