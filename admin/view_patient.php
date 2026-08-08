<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['id'])) { $id = $_GET['id']; } // Get ID from URL

try {
    // Fetch Patient Details
    $stmt = $pdo->prepare("SELECT * FROM Patients WHERE patient_id = ?");
    $stmt->execute([$_GET['id']]);
    $patient = $stmt->fetch();

    // Fetch Appointment History
    $stmt_app = $pdo->prepare("SELECT a.*, d.full_name as doc_name 
                               FROM Appointments a 
                               JOIN Doctors d ON a.doctor_id = d.doctor_id 
                               WHERE a.patient_id = ? 
                               ORDER BY a.appointment_date DESC");
    $stmt_app->execute([$_GET['id']]);
    $history = $stmt_app->fetchAll();
} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Patient Profile | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary-blue: #0077b6; --light-bg: #f8f9fa; --nav-dark: #1e293b; }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', sans-serif; }
        .header-top { background: #fff; padding: 12px 30px; border-bottom: 1px solid #e2e8f0; }
        .profile-card { background: #fff; border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }
        .history-table { background: #fff; border-radius: 12px; overflow: hidden; }
        .badge-status { font-size: 0.8rem; padding: 5px 12px; border-radius: 20px; }
    </style>
</head>
<body>

<div class="header-top d-flex justify-content-between align-items-center">
    <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-plus me-2"></i>Smart Health Care</h4>
    <a href="manage_patients.php" class="btn btn-outline-secondary btn-sm"><i class="bi bi-arrow-left"></i> Back</a>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-md-4">
            <div class="profile-card p-4 text-center">
                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mx-auto mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                    <?= strtoupper(substr($patient['full_name'], 0, 1)) ?>
                </div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($patient['full_name']) ?></h4>
                <p class="text-muted">ID: #PAT-<?= $patient['patient_id'] ?></p>
                <hr>
                <div class="text-start">
                    <p><strong>DOB:</strong> <?= $patient['date_of_birth'] ?></p>
                    <p><strong>Gender:</strong> <?= $patient['gender'] ?></p>
                    <p><strong>Contact:</strong> <?= $patient['contact_phone'] ?></p>
                </div>
            </div>
        </div>

        <div class="col-md-8">
            <h5 class="fw-bold mb-3"><i class="bi bi-clock-history me-2"></i>Appointment History</h5>
            <div class="history-table border shadow-sm">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Doctor</th>
                            <th>Status</th>
                            <th>Note</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($history)): ?>
                            <tr><td colspan="4" class="text-center py-4">No past appointments found.</td></tr>
                        <?php else: ?>
                            <?php foreach($history as $row): ?>
                            <tr>
                                <td><?= date('d M, Y', strtotime($row['appointment_date'])) ?></td>
                                <td>Dr. <?= htmlspecialchars($row['doc_name']) ?></td>
                                <td><span class="badge bg-info text-dark badge-status"><?= $row['status'] ?></span></td>
                                <td><small><?= htmlspecialchars($row['reason'] ?? 'N/A') ?></small></td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

</body>
</html>