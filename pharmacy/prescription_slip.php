<?php
require_once '../includes/auth.php';
requireRole(['Pharmacist', 'Doctor', 'Nurse']);
$rx_id = (int)($_GET['id'] ?? 0); if (!$rx_id) die("ID required.");
$stmt = $pdo->prepare("SELECT p.*, c.diagnosis, c.is_emergency, pat.full_name, pat.patient_id, pat.gender, pat.blood_group, doc.full_name as doc_name, disp.full_name as disp_name FROM prescriptions p JOIN consultations c ON p.consultation_id = c.consultation_id JOIN patients pat ON c.patient_id = pat.patient_id JOIN users doc ON c.doctor_id = doc.id LEFT JOIN users disp ON p.dispensed_by = disp.id WHERE p.prescription_id = ?");
$stmt->execute([$rx_id]); $rx = $stmt->fetch(); if (!$rx) die("Not found.");
$v = $pdo->prepare("SELECT allergies FROM vitals WHERE patient_id = ? ORDER BY date_time DESC LIMIT 1"); $v->execute([$rx['patient_id']]); $allergies = $v->fetchColumn();
logAction($rx['patient_id'], 'View_Prescription_Slip');
?>
<!DOCTYPE html>
<html><head><title>Slip</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<style>body{font-family:sans-serif;padding:20px;background:#eee;}.slip{max-width:700px;margin:auto;background:#fff;padding:40px;box-shadow:0 0 10px rgba(0,0,0,0.1);}.header{border-bottom:2px solid #000;padding-bottom:10px;margin-bottom:20px;}.allergy{color:red;font-weight:bold;border:1px solid red;padding:5px;margin:10px 0;}.drugs{white-space:pre-line;font-family:monospace;background:#f4f4f4;padding:15px;border-radius:5px;}</style>
</head><body>
<div class="text-center mb-3"><button onclick="window.print()" class="btn btn-primary">Print</button></div>
<div class="slip">
    <div class="header d-flex justify-content-between"><h3>Hospital Management System</h3><h4>RX-<?= $rx_id ?></h4></div>
    <p><strong>Patient:</strong> <?= e($rx['full_name']) ?> (<?= e($rx['patient_id']) ?>)</p>
    <p><strong>Doctor:</strong> <?= e($rx['doc_name']) ?> | <strong>Date:</strong> <?= date('d-M-Y', strtotime($rx['date_time'])) ?></p>
    <p><strong>Diagnosis:</strong> <?= e($rx['diagnosis']) ?></p>
    <?php if ($allergies && strtolower($allergies) !== 'none'): ?><div class="allergy">ALLERGIES: <?= e($allergies) ?></div><?php endif; ?>
    <div class="drugs"><?= e($rx['medication_details']) ?></div>
    <?php if ($rx['dispensed_at']): ?>
        <div class="mt-4 text-success"><strong>Dispensed By:</strong> <?= e($rx['disp_name']) ?> on <?= date('d-M-Y h:i A', strtotime($rx['dispensed_at'])) ?></div>
    <?php else: ?>
        <div class="mt-4 text-danger">Status: <?= e($rx['status']) ?></div>
    <?php endif; ?>
</div>
</body></html>