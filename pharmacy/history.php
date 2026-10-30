<?php
ob_start();
require_once '../includes/auth.php';
requireRole(['Pharmacist']);
$search = $_GET['search'] ?? '';
$query = "SELECT p.*, pat.full_name, u.full_name as disp FROM prescriptions p JOIN consultations c ON p.consultation_id = c.consultation_id JOIN patients pat ON c.patient_id = pat.patient_id LEFT JOIN users u ON p.dispensed_by = u.id WHERE p.status = 'Dispensed'";
$params = [];
if ($search) { $query .= " AND (pat.full_name LIKE ? OR pat.patient_id LIKE ?)"; $params = ["%$search%", "%$search%"]; }
$query .= " ORDER BY p.dispensed_at DESC LIMIT 100";
$stmt = $pdo->prepare($query); $stmt->execute($params); $records = $stmt->fetchAll();
require_once '../includes/header.php';
?>
<div class="card card-custom mt-4"><div class="card-header-custom"><h4>Dispensing History</h4></div><div class="card-body">
    <form method="GET" class="mb-3 d-flex gap-2"><input type="text" name="search" class="form-control" placeholder="Search patient..." value="<?= e($search) ?>"><button type="submit" class="btn btn-dark">Search</button></form>
    <table class="table">
        <?php foreach($records as $r): ?>
        <tr><td>RX-<?= e($r['prescription_id']) ?></td><td><?= e($r['full_name']) ?></td><td><?= date('d-M-Y h:i A', strtotime($r['dispensed_at'])) ?></td><td><?= e($r['disp']) ?></td><td><a href="prescription_slip.php?id=<?= e($r['prescription_id']) ?>" target="_blank" class="btn btn-sm btn-outline-success">Reprint</a></td></tr>
        <?php endforeach; ?>
    </table>
</div></div>
</body></html>
<?php ob_end_flush(); ?>