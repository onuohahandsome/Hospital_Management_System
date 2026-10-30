<?php
require_once __DIR__ . '/../config/app_config.php';
require_once __DIR__ . '/../config/helpers.php';

$current_role       = $_SESSION['role'] ?? '';
$current_department = $_SESSION['department'] ?? '';
$current_full_name  = $_SESSION['full_name'] ?? ($_SESSION['username'] ?? 'Staff');

if (!function_exists('isActive')) {
    function isActive(string $page): string {
        $current = basename($_SERVER['PHP_SELF'] ?? '');
        return $current === $page ? 'active' : '';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= HOSPITAL_NAME ?> - <?= HOSPITAL_SHORT ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, sans-serif; overflow-x: hidden; margin: 0; }
        .sidebar { height: 100vh; width: 250px; position: fixed; top: 0; left: 0; background: linear-gradient(180deg, #1e293b 0%, #0f172a 100%); color: white; z-index: 1000; overflow-y: auto; }
        .sidebar-brand { padding: 20px 15px; font-size: 1.1rem; font-weight: 700; border-bottom: 1px solid #334155; text-align: center; display: block; color: white; text-decoration: none; }
        .sidebar-brand:hover { color: #38bdf8; }
        .sidebar-user { padding: 15px; border-bottom: 1px solid #334155; text-align: center; }
        .sidebar-user .avatar { width: 55px; height: 55px; border-radius: 50%; background: #38bdf8; color: #0f172a; font-weight: bold; font-size: 1.3rem; display: flex; align-items: center; justify-content: center; margin: 0 auto 8px; }
        .sidebar-user .name { font-size: 0.9rem; font-weight: 600; }
        .sidebar-user .role { font-size: 0.72rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; }
        .sidebar-section { padding: 15px 20px 6px; font-size: 0.7rem; color: #64748b; text-transform: uppercase; letter-spacing: 1.2px; font-weight: 600; }
        .sidebar-nav { padding: 0 0 20px 0; list-style: none; margin: 0; }
        .sidebar-nav li a { display: flex; align-items: center; padding: 11px 20px; color: #cbd5e1; text-decoration: none; font-size: 0.9rem; border-left: 3px solid transparent; transition: 0.2s; }
        .sidebar-nav li a:hover, .sidebar-nav li a.active { background: #0f172a; color: #38bdf8; border-left-color: #38bdf8; }
        .sidebar-nav li a i { width: 22px; text-align: center; margin-right: 12px; }
        .sidebar-nav li.logout-link { margin-top: 15px; border-top: 1px solid #334155; padding-top: 10px; }
        .sidebar-nav li.logout-link a { color: #f87171; }
        .sidebar-nav li.logout-link a:hover { background: #7f1d1d; color: white; border-left-color: #ef4444; }
        .main-content { margin-left: 250px; padding: 20px 25px; min-height: 100vh; }
        .top-bar { background: white; padding: 15px 25px; border-radius: 10px; box-shadow: 0 2px 6px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; margin-bottom: 22px; }
        .top-bar h5 { margin: 0; font-weight: 500; color: #475569; }
        .card-custom { border: none; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.05); background: #fff; margin-bottom: 20px; }
        .card-header-custom { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 20px; border-radius: 10px 10px 0 0 !important; font-weight: 600; }
        .hover-lift { transition: transform 0.2s, box-shadow 0.2s; }
        .hover-lift:hover { transform: translateY(-4px); box-shadow: 0 8px 20px rgba(0,0,0,0.15) !important; }
        @media (max-width: 768px) { .sidebar { width: 0; padding: 0; overflow: hidden; } .main-content { margin-left: 0; padding: 15px; } }
    </style>
</head>
<body>

<?php if (isset($_SESSION['user_id'])): ?>
    <div class="sidebar">
        <a href="<?= BASE_URL ?>dashboard.php" class="sidebar-brand">
            <i class="fas fa-hospital-symbol text-info"></i> <?= HOSPITAL_SHORT ?>
        </a>
        <div class="sidebar-user">
            <div class="avatar"><?= strtoupper(substr($current_full_name, 0, 1)) ?></div>
            <div class="name"><?= e($current_full_name) ?></div>
            <div class="role"><?= e($current_role) ?><?= $current_department ? ' • ' . e($current_department) : '' ?></div>
        </div>
        <ul class="sidebar-nav">
            <div class="sidebar-section">Main</div>
            <li><a href="<?= BASE_URL ?>dashboard.php" class="<?= isActive('dashboard.php') ?>"><i class="fas fa-home"></i> Dashboard</a></li>

            <?php if ($current_role === 'Records'): ?>
                <div class="sidebar-section">Patient Management</div>
                <li><a href="<?= BASE_URL ?>records/dashboard.php" class="<?= isActive('dashboard.php') && strpos($_SERVER['PHP_SELF'], 'records') ? 'active' : '' ?>"><i class="fas fa-clipboard-list"></i> Records Dashboard</a></li>
                <li><a href="<?= BASE_URL ?>records/register.php" class="<?= isActive('register.php') ?>"><i class="fas fa-user-plus"></i> Register New Patient</a></li>
                <li><a href="<?= BASE_URL ?>records/check_in.php" class="<?= isActive('check_in.php') ?>"><i class="fas fa-user-check"></i> Check-In Returning</a></li>
                <li><a href="<?= BASE_URL ?>records/assign_doctor.php" class="<?= isActive('assign_doctor.php') ?>"><i class="fas fa-user-md"></i> Reassign Doctor</a></li>
            <?php endif; ?>

            <?php if ($current_role === 'Nurse'): ?>
                <div class="sidebar-section">Clinical</div>
                <li><a href="<?= BASE_URL ?>nurse/vitals.php" class="<?= isActive('vitals.php') ?>"><i class="fas fa-heartbeat"></i> Triage & Intake</a></li>
            <?php endif; ?>

            <?php if ($current_role === 'Doctor'): ?>
                <div class="sidebar-section">Clinical</div>
                <li><a href="<?= BASE_URL ?>doctor/queue.php" class="<?= isActive('queue.php') ?>"><i class="fas fa-stethoscope"></i> My Patient Queue</a></li>
                <li><a href="<?= BASE_URL ?>doctor/patients.php" class="<?= isActive('patients.php') ?>"><i class="fas fa-users"></i> My Patients</a></li>
                <li><a href="<?= BASE_URL ?>doctor/revisions.php" class="<?= isActive('revisions.php') ?>"><i class="fas fa-undo text-warning"></i> Returned Prescriptions</a></li>
                <?php if ($current_department === 'Emergency'): ?>
                    <div class="sidebar-section">Emergency</div>
                    <li><a href="<?= BASE_URL ?>emergency/access.php" class="<?= isActive('access.php') ?>"><i class="fas fa-ambulance text-danger"></i> Emergency Access</a></li>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($current_role === 'Pharmacist'): ?>
                <div class="sidebar-section">Pharmacy</div>
                <li><a href="<?= BASE_URL ?>pharmacy/queue.php" class="<?= isActive('queue.php') ?>"><i class="fas fa-pills"></i> Dispensing Queue</a></li>
                <li><a href="<?= BASE_URL ?>pharmacy/history.php" class="<?= isActive('history.php') ?>"><i class="fas fa-history"></i> Dispensing History</a></li>
            <?php endif; ?>

            <?php if ($current_role === 'Admin'): ?>
                <div class="sidebar-section">Administration</div>
                <li><a href="<?= BASE_URL ?>admin/manage_staff.php" class="<?= isActive('manage_staff.php') || isActive('edit_staff.php') ? 'active' : '' ?>"><i class="fas fa-users-cog"></i> Manage Staff</a></li>
                <li><a href="<?= BASE_URL ?>admin/audit_logs.php" class="<?= isActive('audit_logs.php') ?>"><i class="fas fa-history"></i> Audit Logs</a></li>
            <?php endif; ?>

            <li class="logout-link"><a href="<?= BASE_URL ?>logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a></li>
        </ul>
    </div>
    <div class="main-content">
        <div class="top-bar">
            <h5>Welcome back, <strong><?= e($current_full_name) ?></strong></h5>
            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-primary px-3 py-2"><i class="fas fa-user-shield me-1"></i> <?= e($current_role) ?></span>
                <?php if ($current_department): ?><span class="badge bg-info text-dark px-3 py-2"><?= e($current_department) ?></span><?php endif; ?>
                <a href="<?= BASE_URL ?>logout.php" class="btn btn-sm btn-outline-danger"><i class="fas fa-sign-out-alt"></i></a>
            </div>
        </div>
<?php else: ?>
    <div class="container">
<?php endif; ?>