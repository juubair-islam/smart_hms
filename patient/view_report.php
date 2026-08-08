<?php
session_start();
require '../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}

$patient_id = $_SESSION['user_id'];
$appointment_id = $_GET['id'] ?? null;

if (!$appointment_id) {
    header('Location: appointments.php');
    exit;
}

try {
    // 2. Fetch Consultation, Doctor, and Bill Status
    $stmt = $pdo->prepare("SELECT c.*, d.full_name as doctor_name, d.expertise, d.qualification, 
                                 a.appointment_date, b.status as bill_status
                          FROM Consultations c
                          JOIN Doctors d ON c.doctor_id = d.doctor_id
                          JOIN Appointments a ON c.appointment_id = a.appointment_id
                          JOIN Billing b ON a.appointment_id = b.appointment_id
                          WHERE c.appointment_id = ? AND c.patient_id = ?");
    $stmt->execute([$appointment_id, $patient_id]);
    $report = $stmt->fetch();

    if (!$report || $report['bill_status'] !== 'Paid') {
        die("Access Denied. Please complete payment first.");
    }

    // 3. Fetch Prescriptions
    $stmt_pres = $pdo->prepare("SELECT * FROM Prescriptions WHERE consultation_id = ?");
    $stmt_pres->execute([$report['consultation_id']]);
    $prescriptions = $stmt_pres->fetchAll();

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Report | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #0077b6; --bg: #f4f7fe; --sidebar: #ffffff; }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; }
        
        /* Unified Sidebar CSS */
        .sidebar { background: var(--sidebar); min-height: 100vh; border-right: 1px solid #e2e8f0; position: fixed; width: 280px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; }
        .brand-box { padding: 30px 25px; line-height: 1.2; }
        .brand-text-top { font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; }
        .brand-text-bottom { font-size: 0.9rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }
        .nav-link { color: #64748b; padding: 14px 20px; border-radius: 12px; margin: 4px 20px; display: flex; align-items: center; transition: 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: #eff6ff; color: var(--primary); font-weight: 600; }

        /* Report Card Styling */
        .report-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); padding: 40px; }
        .rx-header { border-bottom: 2px dashed #e2e8f0; padding-bottom: 20px; margin-bottom: 30px; }
        .info-label { font-size: 0.75rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; }
        .data-value { font-weight: 600; color: #1e293b; }
        .prescription-table { background: #f8fafc; border-radius: 15px; overflow: hidden; }
        .prescription-table th { background: #f1f5f9; color: #475569; font-size: 0.8rem; border: none; }
        .prescription-table td { border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        .rx-icon { font-size: 2rem; color: var(--primary); font-weight: 900; font-family: serif; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand-box">
        <span class="brand-text-top">Smart Health Care</span>
        <span class="brand-text-bottom">Management System</span>
    </div>
    <nav class="nav flex-column mt-4">
        <a class="nav-link" href="dashboard.php"><i class="bi bi-house-heart me-3"></i> Overview</a>
        <a class="nav-link active" href="appointments.php"><i class="bi bi-calendar-event me-3"></i> My Bookings</a>
        
        <a class="nav-link" href="medical_records.php"><i class="bi bi-file-earmark-medical me-3"></i> Health Records</a>
         <a class="nav-link" href="ai_symptom_checker.php"><i class="bi bi-cpu me-3"></i> AI Disease Checker</a>
       
        <a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-3"></i> Billing</a>
        <div class="mt-auto px-4 pt-5"><hr></div>
        <a class="nav-link text-danger mb-4" href="../actions/logout.php"><i class="bi bi-power me-3"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <a href="appointments.php" class="text-decoration-none small fw-bold text-muted">
                <i class="bi bi-arrow-left me-1"></i> BACK TO BOOKINGS
            </a>
            <h3 class="fw-bold text-dark mt-2">Consultation Report</h3>
        </div>
        <button onclick="window.print()" class="btn btn-outline-primary rounded-pill px-4 fw-bold">
            <i class="bi bi-printer me-2"></i> Download PDF
        </button>
    </div>

    <div class="report-card">
        <div class="rx-header d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center">
                <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 60px; height: 60px;">
                    <i class="bi bi-person-badge fs-3"></i>
                </div>
                <div>
                    <h4 class="fw-bold mb-0">Dr. <?= htmlspecialchars($report['doctor_name']) ?></h4>
                    <span class="text-muted small"><?= htmlspecialchars($report['expertise']) ?> | <?= htmlspecialchars($report['qualification']) ?></span>
                </div>
            </div>
            <div class="text-end">
                <div class="info-label">Date of Consultation</div>
                <div class="data-value"><?= date('d M, Y', strtotime($report['appointment_date'])) ?></div>
            </div>
        </div>

        <div class="row g-4 mb-5">
            <div class="col-md-6">
                <div class="p-3 border rounded-4 bg-light">
                    <div class="info-label mb-2">Chief Complaints</div>
                    <p class="mb-0 text-dark"><?= nl2br(htmlspecialchars($report['symptoms'])) ?></p>
                </div>
            </div>
            <div class="col-md-6">
                <div class="p-3 border rounded-4 bg-primary-subtle border-primary-subtle">
                    <div class="info-label mb-2 text-primary">Final Diagnosis</div>
                    <p class="mb-0 fw-bold text-primary fs-5"><?= nl2br(htmlspecialchars($report['diagnosis'])) ?></p>
                </div>
            </div>
        </div>

        <div class="mb-4">
            <div class="d-flex align-items-center mb-3">
                <span class="rx-icon me-3">Rx</span>
                <h5 class="fw-bold mb-0">Medication Orders</h5>
            </div>
            
            <div class="table-responsive prescription-table">
                <table class="table mb-0">
                    <thead>
                        <tr>
                            <th class="ps-4">Medicine & Strength</th>
                            <th>Dosage Schedule</th>
                            <th>Duration</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($prescriptions)): ?>
                            <tr><td colspan="3" class="text-center py-4 text-muted">No medications prescribed for this visit.</td></tr>
                        <?php else: ?>
                            <?php foreach($prescriptions as $p): ?>
                                <tr>
                                    <td class="ps-4 py-3">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['medicine_name']) ?></div>
                                    </td>
                                    <td><span class="badge bg-white text-dark border px-3"><?= htmlspecialchars($p['dosage']) ?></span></td>
                                    <td class="text-muted small fw-bold"><?= htmlspecialchars($p['duration']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="p-4 border-start border-4 border-primary bg-light rounded-end-4 mb-4">
            <div class="info-label mb-2">Doctor's Advice & Follow-up</div>
            <p class="mb-0 italic text-muted">"<?= nl2br(htmlspecialchars($report['advice'] ?: 'Follow standard care and rest.')) ?>"</p>
        </div>

        <div class="mt-5 text-center">
            <div class="small text-muted mb-2">This is a digitally verified medical report.</div>
            <img src="https://api.qrserver.com/v1/create-qr-code/?size=80x80&data=Verify-Report-<?= $appointment_id ?>" alt="QR Verification" class="opacity-50">
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>