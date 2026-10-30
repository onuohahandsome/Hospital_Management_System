<?php
require_once 'includes/auth.php';
require_once 'includes/header.php';

$total_patients = $pdo->query("SELECT COUNT(*) FROM patients")->fetchColumn();
$today_checkins = $pdo->query("SELECT COUNT(*) FROM visits WHERE DATE(check_in_time) = CURDATE()")->fetchColumn();
$waiting_triage = $pdo->query("SELECT COUNT(*) FROM visits WHERE visit_status = 'Awaiting_Triage' AND DATE(check_in_time) = CURDATE()")->fetchColumn();
$pending_rx = $pdo->query("SELECT COUNT(*) FROM prescriptions WHERE status = 'Issued'")->fetchColumn();
$revisions = 0;
$my_queue = 0;
if ($_SESSION['role'] == 'Doctor') {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM prescriptions WHERE status = 'Revision_Requested'");
    $stmt->execute();
    $revisions = $stmt->fetchColumn();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM visits WHERE assigned_doctor_id = ? AND visit_status = 'Awaiting_Doctor' AND DATE(check_in_time) = CURDATE()");
    $stmt->execute([$_SESSION['user_id']]);
    $my_queue = $stmt->fetchColumn();
}
?>
<div class="row mb-4 mt-3">
    <div class="col-md-3">
        <div class="card card-custom p-3" style="border-left:5px solid #1a73e8;">
            <h6 class="text-muted mb-1">Total Patients</h6>
            <h2 class="mb-0 fw-bold"><?= $total_patients ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3" style="border-left:5px solid #34a853;">
            <h6 class="text-muted mb-1">Today's Check-Ins</h6>
            <h2 class="mb-0 fw-bold"><?= $today_checkins ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3" style="border-left:5px solid #fbbc04;">
            <h6 class="text-muted mb-1">Waiting for Triage</h6>
            <h2 class="mb-0 fw-bold"><?= $waiting_triage ?></h2>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card card-custom p-3" style="border-left:5px solid #db4437;">
            <h6 class="text-muted mb-1">Pending Rx</h6>
            <h2 class="mb-0 fw-bold"><?= $pending_rx ?></h2>
        </div>
    </div>
</div>
<?php if ($_SESSION['role'] == 'Doctor' && $revisions > 0): ?>
    <div class="alert alert-danger d-flex justify-content-between">
        <div><i class="fas fa-exclamation-triangle"></i> <strong><?= $revisions ?></strong> prescription(s) returned for revision.</div><a href="<?= BASE_URL ?>doctor/revisions.php" class="btn btn-sm btn-danger">Review</a>
    </div>
<?php endif; ?>
<div class="row justify-content-center mt-4">
    <div class="col-md-10">
        <div class="card card-custom p-5 text-center">
            <h3>Welcome, <span class="text-primary"><?= e($_SESSION['full_name']) ?></span></h3>
            <div class="row g-3 mt-2">
                <?php if ($_SESSION['role'] == 'Records'): ?>
                    <div class="col-md-6"><a href="records/register.php" class="text-decoration-none">
                            <div class="card p-4 border-primary h-100">
                                <h5>Register Patient</h5>
                            </div>
                        </a></div>
                    <div class="col-md-6"><a href="records/check_in.php" class="text-decoration-none">
                            <div class="card p-4 border-success h-100">
                                <h5>Check-In Returning</h5>
                            </div>
                        </a></div>
                <?php elseif ($_SESSION['role'] == 'Nurse'): ?>
                    <div class="col-md-6 mx-auto"><a href="nurse/vitals.php" class="text-decoration-none">
                            <div class="card p-4 border-info h-100">
                                <h5>Triage</h5>
                            </div>
                        </a></div>
                <?php elseif ($_SESSION['role'] == 'Doctor'): ?>
                    <div class="col-md-4"><a href="doctor/queue.php" class="text-decoration-none">
                            <div class="card p-4 border-primary h-100">
                                <h5>My Queue (<?= $my_queue ?>)</h5>
                            </div>
                        </a></div>
                    <div class="col-md-4"><a href="doctor/patients.php" class="text-decoration-none">
                            <div class="card p-4 border-success h-100">
                                <h5>My Patients</h5>
                            </div>
                        </a></div>
                    <div class="col-md-4"><a href="doctor/revisions.php" class="text-decoration-none">
                            <div class="card p-4 border-warning h-100">
                                <h5>Returned Rx (<?= $revisions ?>)</h5>
                            </div>
                        </a></div>
                <?php elseif ($_SESSION['role'] == 'Pharmacist'): ?>
                    <div class="col-md-6 mx-auto"><a href="pharmacy/queue.php" class="text-decoration-none">
                            <div class="card p-4 border-warning h-100">
                                <h5>Dispensing Queue</h5>
                            </div>
                        </a></div>
                <?php elseif ($_SESSION['role'] == 'Admin'): ?>
                    <div class="col-md-6"><a href="admin/manage_staff.php" class="text-decoration-none">
                            <div class="card p-4 border-dark h-100">
                                <h5>Manage Staff</h5>
                            </div>
                        </a></div>
                    <div class="col-md-6"><a href="admin/audit_logs.php" class="text-decoration-none">
                            <div class="card p-4 border-secondary h-100">
                                <h5>Audit Logs</h5>
                            </div>
                        </a></div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</body>

</html>