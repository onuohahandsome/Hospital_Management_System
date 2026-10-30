<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Admin']);
$message = "";
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    try {
        $pdo->prepare("INSERT INTO users (username, password_hash, role, department, full_name, email) VALUES (?,?,?,?,?,?)")->execute([$_POST['username'], $password, $_POST['role'], $_POST['department'], $_POST['full_name'], $_POST['email']]);
        $message = "<div class='alert alert-success'>Created.</div>";
    } catch (Exception $e) { $message = "<div class='alert alert-danger'>Error: Username exists.</div>"; }
}
$staff = $pdo->query("SELECT * FROM users ORDER BY role")->fetchAll();
require_once '../includes/header.php';
?>
<div class="row">
    <div class="col-md-7">
        <div class="card card-custom"><div class="card-header-custom"><h5>Staff Directory</h5></div><div class="card-body">
            <table class="table">
                <?php foreach($staff as $s): ?>
                <tr><td><?= e($s['full_name']) ?><br><small><?= e($s['role']) ?></small></td><td><a href="edit_staff.php?id=<?= e($s['id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a></td></tr>
                <?php endforeach; ?>
            </table>
        </div></div>
    </div>
    <div class="col-md-5">
        <div class="card card-custom"><div class="card-header-custom"><h5>Create Staff</h5></div><div class="card-body">
            <?= $message ?>
            <form method="POST">
                <div class="mb-2"><input type="text" name="full_name" class="form-control" placeholder="Full Name" required></div>
                <div class="mb-2"><input type="email" name="email" class="form-control" placeholder="Email" required></div>
                <div class="mb-2"><input type="text" name="username" class="form-control" placeholder="Username" required></div>
                <div class="mb-2"><input type="password" name="password" class="form-control" placeholder="Password" required></div>
                <div class="row mb-2">
                    <div class="col-6"><select name="role" class="form-select"><option>Doctor</option><option>Nurse</option><option>Pharmacist</option><option>Records</option><option>Admin</option></select></div>
                    <div class="col-6"><select name="department" class="form-select"><option>OPD</option><option>Emergency</option></select></div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Create</button>
            </form>
        </div></div>
    </div>
</div>
</body></html>
<?php ob_end_flush(); ?>