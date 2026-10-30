<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Records']);

$search = $_GET['search'] ?? ''; $success_patient = null; $error_message = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $full_name = trim($_POST['full_name'] ?? ''); $category = $_POST['category'] ?? '';
    $dob = $_POST['dob'] ?? null; $gender = $_POST['gender'] ?? 'Male';
    $phone = trim($_POST['phone'] ?? ''); $address = trim($_POST['address'] ?? '');
    $blood_group = $_POST['blood_group'] ?? 'Unknown'; $assigned_doctor = (int)($_POST['assigned_doctor'] ?? 0);
    $entered_id = trim($_POST['patient_id_input'] ?? '');

    if ($full_name === '') { $error_message = "Full name is required."; }
    elseif ($assigned_doctor <= 0) { $error_message = "Please select a doctor."; }
    else {
        try {
            $pdo->beginTransaction();
            if ($category === 'Student') {
                if (!preg_match('/^\d{4}\/\d{5,6}$/', $entered_id)) throw new Exception("Student ID must be YYYY/NNNNNN.");
                $pid = $entered_id;
            } elseif ($category === 'Staff') {
                if (!preg_match('/^[A-Za-z0-9\/\-\.]{4,25}$/', $entered_id)) throw new Exception("Staff ID must be 4-25 chars.");
                $pid = $entered_id;
            } else {
                $year = date('Y'); $stmt = $pdo->prepare("SELECT patient_id FROM patients WHERE patient_id LIKE ? ORDER BY patient_id DESC LIMIT 1 FOR UPDATE"); $stmt->execute(["TR-$year-%"]); $last = $stmt->fetchColumn();
                $seq = $last ? (int)substr($last, -3) + 1 : 1; $pid = "TR-$year-" . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }
            $chk = $pdo->prepare("SELECT 1 FROM patients WHERE patient_id = ?"); $chk->execute([$pid]);
            if ($chk->fetch()) throw new Exception("Registration number '$pid' already registered. Use Check-In.");
            $doc_check = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND role = 'Doctor'"); $doc_check->execute([$assigned_doctor]); $doctor_name = $doc_check->fetchColumn();
            if (!$doctor_name) throw new Exception("Doctor not found.");

            $pdo->prepare("INSERT INTO patients (patient_id, full_name, category, date_of_birth, gender, phone, address, blood_group, assigned_doctor_id) VALUES (?,?,?,?,?,?,?,?,?)")->execute([$pid, $full_name, $category, $dob, $gender, $phone, $address, $blood_group, $assigned_doctor]);
            $pdo->prepare("INSERT INTO visits (patient_id, assigned_doctor_id, checked_in_by) VALUES (?,?,?)")->execute([$pid, $assigned_doctor, $_SESSION['user_id']]);
            logAction($pid, 'Register_Patient'); logAction($pid, 'Check_In'); $pdo->commit();
            $success_patient = ['reg_no' => $pid, 'name' => $full_name, 'category' => $category, 'gender' => $gender, 'blood_group' => $blood_group, 'doctor' => $doctor_name];
        } catch (Exception $e) { if ($pdo->inTransaction()) $pdo->rollBack(); $error_message = $e->getMessage(); }
    }
}
$doctors = $pdo->query("SELECT id, full_name, department FROM users WHERE role = 'Doctor' ORDER BY full_name")->fetchAll();
require_once '../includes/header.php';
?>
<div class="row">
    <div class="col-md-6">
        <?php if ($error_message): ?><div class="alert alert-danger mt-3"><strong>Error:</strong> <?= e($error_message) ?></div><?php endif; ?>
        <div class="card card-custom mt-4">
            <div class="card-header-custom"><h5 class="mb-0">Register New Patient</h5></div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?= e($_SESSION['csrf_token']) ?>">
                    <div class="mb-3"><label class="form-label">Full Name</label><input type="text" name="full_name" class="form-control" required></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Category</label><select name="category" id="categorySelect" class="form-select" required><option value="Student">Student</option><option value="Staff">Staff</option><option value="TR">TR</option></select></div>
                        <div class="col-6 mb-3"><label class="form-label">Gender</label><select name="gender" class="form-select"><option>Male</option><option>Female</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label fw-bold" id="idLabel">Student Registration Number *</label><input type="text" name="patient_id_input" id="idInput" class="form-control" required placeholder="e.g., 2022/250171"><small class="text-muted" id="idHint">Enter UNN matriculation number.</small></div>
                    <div class="row">
                        <div class="col-6 mb-3"><label class="form-label">Date of Birth</label><input type="date" name="dob" class="form-control" required></div>
                        <div class="col-6 mb-3"><label class="form-label">Blood Group</label><select name="blood_group" class="form-select"><option>Unknown</option><option>A+</option><option>O+</option><option>B+</option><option>AB+</option></select></div>
                    </div>
                    <div class="mb-3"><label class="form-label">Phone</label><input type="text" name="phone" class="form-control"></div>
                    <div class="mb-3"><label class="form-label">Address</label><textarea name="address" class="form-control" rows="2"></textarea></div>
                    <div class="mb-3 p-3 bg-light rounded border-start border-primary border-3">
                        <label class="form-label fw-bold">Assign Doctor</label>
                        <select name="assigned_doctor" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php foreach($doctors as $d): ?><option value="<?= e($d['id']) ?>"><?= e($d['full_name']) ?> (<?= e($d['department']) ?>)</option><?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Register Patient</button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <?php if ($success_patient): ?>
        <div class="card card-custom mt-4 border-success border-3">
            <div class="card-header-custom bg-success text-white"><h5 class="mb-0">Patient Registered</h5></div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr><th>Registration No.</th><td><span class="badge bg-primary fs-6"><?= e($success_patient['reg_no']) ?></span></td></tr>
                    <tr><th>Name</th><td><?= e($success_patient['name']) ?></td></tr>
                    <tr class="table-info"><th>Doctor</th><td><?= e($success_patient['doctor']) ?></td></tr>
                </table>
                <a href="register.php" class="btn btn-outline-primary w-100">Register Another</a>
            </div>
        </div>
        <?php endif; ?>
        <div class="card card-custom mt-4">
            <div class="card-header-custom"><h5 class="mb-0">Search Patient</h5></div>
            <div class="card-body">
                <form method="GET" class="mb-3 d-flex gap-2"><input type="text" name="search" class="form-control" placeholder="Name, Reg No..." value="<?= e($search) ?>"><button type="submit" class="btn btn-dark">Search</button></form>
                <table class="table table-sm">
                    <tbody>
                        <?php
                        if ($search) { $stmt = $pdo->prepare("SELECT p.*, u.full_name as doctor_name FROM patients p LEFT JOIN users u ON p.assigned_doctor_id = u.id WHERE p.full_name LIKE ? OR p.patient_id LIKE ?"); $stmt->execute(["%$search%", "%$search%"]); }
                        else { $stmt = $pdo->query("SELECT p.*, u.full_name as doctor_name FROM patients p LEFT JOIN users u ON p.assigned_doctor_id = u.id ORDER BY p.created_at DESC LIMIT 5"); }
                        foreach($stmt->fetchAll() as $p): ?>
                        <tr>
                            <td><?= e($p['patient_id']) ?><br><small><?= e($p['full_name']) ?></small></td>
                            <td><a href="edit_patient.php?pid=<?= e($p['patient_id']) ?>" class="btn btn-sm btn-outline-primary">Edit</a></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('categorySelect').addEventListener('change', function() {
    const cat = this.value; const label = document.getElementById('idLabel'); const input = document.getElementById('idInput'); const hint = document.getElementById('idHint');
    if (cat === 'Student') { label.innerHTML = 'Student Registration Number *'; input.placeholder = 'e.g., 2022/250171'; input.disabled = false; input.required = true; hint.textContent = 'Enter UNN matriculation number.'; }
    else if (cat === 'Staff') { label.innerHTML = 'Staff ID *'; input.placeholder = 'e.g., UNN/STAFF/0456'; input.disabled = false; input.required = true; hint.textContent = 'Enter staff ID.'; }
    else { label.innerHTML = 'Registration Number (Auto)'; input.value = ''; input.disabled = true; input.required = false; hint.textContent = 'Auto-generates TR-YYYY-NNN.'; }
});
</script>
</body></html>