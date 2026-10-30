<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Nurse']);
$error = ""; $success = ""; $prefill_pid = $_GET['pid'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $pid = trim($_POST['patient_id'] ?? ''); $chief = trim($_POST['chief_complaint'] ?? ''); $bp = trim($_POST['bp'] ?? '');
    $temp = (float)($_POST['temp'] ?? 0); $pulse = (int)($_POST['pulse'] ?? 0); $rr = (int)($_POST['resp_rate'] ?? 0);
    $spo2 = (int)($_POST['spo2'] ?? 0); $weight = (float)($_POST['weight'] ?? 0); $height = (float)($_POST['height'] ?? 0);
    $pain = (int)($_POST['pain_score'] ?? 0); $allergies = trim($_POST['allergies'] ?? ''); $medications = trim($_POST['medications'] ?? '');

    if ($chief === '') $error = "Chief Complaint required.";
    elseif (!preg_match('/^\d{2,3}\/\d{2,3}$/', $bp)) $error = "BP format error (120/80).";
    elseif ($pulse < 30 || $pulse > 220) $error = "Pulse out of range.";
    else {
        $stmt = $pdo->prepare("SELECT full_name FROM patients WHERE patient_id = ?"); $stmt->execute([$pid]);
        if (!$stmt->fetch()) { $error = "Patient not found."; }
        else {
            $check_in = $pdo->prepare("SELECT visit_id FROM visits WHERE patient_id = ? AND visit_status = 'Awaiting_Triage' AND DATE(check_in_time) = CURDATE()"); $check_in->execute([$pid]); $active_visit = $check_in->fetch();
            if (!$active_visit) { $error = "Not checked in today."; }
            else {
                $pdo->prepare("INSERT INTO vitals (patient_id, recorded_by, chief_complaint, blood_pressure, temperature, pulse, respiratory_rate, oxygen_saturation, weight_kg, height_cm, pain_score, allergies, current_medications) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)")->execute([$pid, $_SESSION['user_id'], $chief, $bp, $temp, $pulse, $rr, $spo2, $weight, $height, $pain, $allergies, $medications]);
                $pdo->prepare("UPDATE visits SET visit_status = 'Awaiting_Doctor' WHERE visit_id = ?")->execute([$active_visit['visit_id']]);
                logAction($pid, 'Record_Vitals'); header("Location: vitals.php?success=1"); ob_end_flush(); exit();
            }
        }
    }
}
if (isset($_GET['success'])) $success = "Intake saved. Patient queued for Doctor.";
$waiting = $pdo->query("SELECT v.visit_id, v.patient_id, p.full_name FROM visits v JOIN patients p ON v.patient_id = p.patient_id WHERE v.visit_status = 'Awaiting_Triage' AND DATE(v.check_in_time) = CURDATE()")->fetchAll();
require_once '../includes/header.php';
?>
<div class="row">
    <div class="col-md-8">
        <?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div class="alert alert-success"><?= e($success) ?></div><?php endif; ?>
        <div class="card card-custom mt-3"><div class="card-header-custom"><h5>Nurse Triage</h5></div><div class="card-body">
            <form method="POST">
                <div class="mb-3"><label>Patient ID</label><input type="text" name="patient_id" class="form-control" value="<?= e($prefill_pid) ?>" required></div>
                <div class="mb-3"><label>Chief Complaint</label><textarea name="chief_complaint" class="form-control" required></textarea></div>
                <div class="row">
                    <div class="col-6 mb-3"><label>BP</label><input type="text" name="bp" class="form-control" required placeholder="120/80"></div>
                    <div class="col-6 mb-3"><label>Pulse</label><input type="number" name="pulse" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-4 mb-3"><label>RR</label><input type="number" name="resp_rate" class="form-control" required></div>
                    <div class="col-4 mb-3"><label>SpO2</label><input type="number" name="spo2" class="form-control" required></div>
                    <div class="col-4 mb-3"><label>Temp</label><input type="number" step="0.1" name="temp" class="form-control" required></div>
                </div>
                <div class="row">
                    <div class="col-6 mb-3"><label>Weight (kg)</label><input type="number" step="0.1" name="weight" class="form-control" required></div>
                    <div class="col-6 mb-3"><label>Height (cm)</label><input type="number" step="0.1" name="height" class="form-control" required></div>
                </div>
                <div class="mb-3"><label>Pain Score (0-10)</label><input type="number" name="pain_score" class="form-control" required></div>
                <div class="mb-3"><label>Allergies</label><input type="text" name="allergies" class="form-control" placeholder="None"></div>
                <div class="mb-3"><label>Current Meds</label><textarea name="medications" class="form-control" placeholder="None"></textarea></div>
                <button type="submit" class="btn btn-info text-white w-100">Save & Queue for Doctor</button>
            </form>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card card-custom mt-3"><div class="card-header-custom"><h6>Waiting for Triage</h6></div><div class="card-body p-0">
            <?php foreach($waiting as $w): ?><a href="?pid=<?= e($w['patient_id']) ?>" class="list-group-item list-group-item-action"><?= e($w['full_name']) ?> (<?= e($w['patient_id']) ?>)</a><?php endforeach; ?>
        </div></div>
    </div>
</div>
</body></html>