<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Doctor']);
$pid = $_GET['pid'] ?? null; if (!$pid) die("No patient.");

$can_access = false;
$chk = $pdo->prepare("SELECT 1 FROM patients WHERE patient_id = ? AND assigned_doctor_id = ?"); $chk->execute([$pid, $_SESSION['user_id']]); if ($chk->fetch()) $can_access = true;
$chk = $pdo->prepare("SELECT 1 FROM visits WHERE patient_id = ? AND assigned_doctor_id = ? AND visit_status = 'Awaiting_Doctor' AND DATE(check_in_time) = CURDATE()"); $chk->execute([$pid, $_SESSION['user_id']]); if ($chk->fetch()) $can_access = true;
$has_emergency = isset($_SESSION['emergency_grants'][$pid]) && $_SESSION['emergency_grants'][$pid] > time(); if ($has_emergency) $can_access = true;
if (!$can_access) { die("403 Forbidden.</body></html>"); }
$is_emergency = $has_emergency ? 1 : 0;
$error = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $notes = trim($_POST['notes']); $diagnosis = trim($_POST['diagnosis']);
    $drugs = [];
    for ($i = 1; $i <= 3; $i++) {
        $name = trim($_POST["drug_name_$i"] ?? ''); $dose = trim($_POST["drug_dose_$i"] ?? ''); $freq = trim($_POST["drug_freq_$i"] ?? ''); $dur = trim($_POST["drug_dur_$i"] ?? ''); $route = trim($_POST["drug_route_$i"] ?? '');
        if ($name !== '') { $line = $name; if ($dose) $line .= " ({$dose})"; if ($freq) $line .= " - {$freq}"; if ($dur) $line .= " x {$dur}"; if ($route) $line .= " [{$route}]"; $drugs[] = $line; }
    }
    $medication_details = implode("\n", $drugs);
    if ($notes === '' || $diagnosis === '') { $error = "Notes and Dx required."; }
    else {
        try {
            $pdo->beginTransaction();
            $pdo->prepare("INSERT INTO consultations (patient_id, doctor_id, notes, diagnosis, is_emergency) VALUES (?,?,?,?,?)")->execute([$pid, $_SESSION['user_id'], $notes, $diagnosis, $is_emergency]);
            $consult_id = $pdo->lastInsertId();
            if ($medication_details !== '') { $pdo->prepare("INSERT INTO prescriptions (consultation_id, medication_details, status) VALUES (?,?,'Issued')")->execute([$consult_id, $medication_details]); logAction($pid, 'Issue_Prescription'); }
            $pdo->prepare("UPDATE vitals SET queue_status = 'Seen' WHERE patient_id = ? AND queue_status = 'Awaiting_Doctor'")->execute([$pid]);
            $new_status = ($medication_details === '') ? 'Completed' : 'Awaiting_Pharmacy';
            $pdo->prepare("UPDATE visits SET visit_status = ? WHERE patient_id = ? AND visit_status = 'Awaiting_Doctor' AND DATE(check_in_time) = CURDATE()")->execute([$new_status, $pid]);
            logAction($pid, 'Add_Consultation'); $pdo->commit();
            header("Location: epr.php?pid=" . urlencode($pid)); ob_end_flush(); exit();
        } catch (Exception $e) { $pdo->rollBack(); $error = $e->getMessage(); }
    }
}
$stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?"); $stmt->execute([$pid]); $patient = $stmt->fetch();
$stmt = $pdo->prepare("SELECT * FROM vitals WHERE patient_id = ? ORDER BY date_time DESC LIMIT 1"); $stmt->execute([$pid]); $vitals = $stmt->fetch();
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4"><div class="card-header-custom"><h4>Consultation: <?= e($patient['full_name']) ?></h4></div><div class="card-body">
    <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
    <?php if ($vitals): ?><div class="alert alert-info">Allergies: <strong><?= e($vitals['allergies']) ?></strong></div><?php endif; ?>
    <form method="POST">
        <div class="mb-3"><label>Notes</label><textarea name="notes" class="form-control" rows="2" required></textarea></div>
        <div class="mb-3"><label>Diagnosis</label><input type="text" name="diagnosis" class="form-control" required></div>
        <div class="row mb-2">
            <div class="col-4"><input type="text" name="drug_name_1" class="form-control" placeholder="Drug 1"></div>
            <div class="col-2"><input type="text" name="drug_dose_1" class="form-control" placeholder="Dose"></div>
            <div class="col-2"><input type="text" name="drug_freq_1" class="form-control" placeholder="Freq"></div>
            <div class="col-2"><input type="text" name="drug_dur_1" class="form-control" placeholder="Dur"></div>
            <div class="col-2"><select name="drug_route_1" class="form-select"><option>Oral</option><option>IV</option></select></div>
        </div>
        <div class="row mb-2">
            <div class="col-4"><input type="text" name="drug_name_2" class="form-control" placeholder="Drug 2 (opt)"></div>
            <div class="col-2"><input type="text" name="drug_dose_2" class="form-control" placeholder="Dose"></div>
            <div class="col-2"><input type="text" name="drug_freq_2" class="form-control" placeholder="Freq"></div>
            <div class="col-2"><input type="text" name="drug_dur_2" class="form-control" placeholder="Dur"></div>
            <div class="col-2"><select name="drug_route_2" class="form-select"><option>Oral</option><option>IV</option></select></div>
        </div>
        <div class="row mb-3">
            <div class="col-4"><input type="text" name="drug_name_3" class="form-control" placeholder="Drug 3 (opt)"></div>
            <div class="col-2"><input type="text" name="drug_dose_3" class="form-control" placeholder="Dose"></div>
            <div class="col-2"><input type="text" name="drug_freq_3" class="form-control" placeholder="Freq"></div>
            <div class="col-2"><input type="text" name="drug_dur_3" class="form-control" placeholder="Dur"></div>
            <div class="col-2"><select name="drug_route_3" class="form-select"><option>Oral</option><option>IV</option></select></div>
        </div>
        <button type="submit" class="btn btn-primary">Save & Send to Pharmacy</button>
    </form>
</div></div>
</body></html>
<?php ob_end_flush(); ?>