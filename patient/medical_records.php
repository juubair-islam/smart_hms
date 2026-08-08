<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';
$patient_id = $_SESSION['user_id'];

try {
    // 1. Unified Profile Header Data
    $stmt = $pdo->prepare("SELECT * FROM Patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    $dob = new DateTime($patient['date_of_birth']);
    $calculated_age = (new DateTime())->diff($dob)->y;

    // 2. Fetch Consultations (The new primary record source)
    // We join with Doctors to show specialist names and Expertise
    $stmt_records = $pdo->prepare("SELECT c.*, d.full_name as d_name, d.expertise, a.appointment_date 
                                   FROM Consultations c 
                                   JOIN Doctors d ON c.doctor_id = d.doctor_id 
                                   JOIN Appointments a ON c.appointment_id = a.appointment_id
                                   WHERE c.patient_id = ? 
                                   ORDER BY a.appointment_date DESC");
    $stmt_records->execute([$patient_id]);
    $records = $stmt_records->fetchAll();

} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Health Records | Smart Health Care Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #0077b6; --bg: #f4f7fe; --sidebar: #ffffff; }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; }
        
        .sidebar { background: var(--sidebar); min-height: 100vh; border-right: 1px solid #e2e8f0; position: fixed; width: 280px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; }
        .brand-box { padding: 30px 25px; line-height: 1.2; }
        .brand-text-top { font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; }
        .brand-text-bottom { font-size: 0.9rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }

        .nav-link { color: #64748b; padding: 14px 20px; border-radius: 12px; margin: 4px 20px; display: flex; align-items: center; transition: 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: #eff6ff; color: var(--primary); font-weight: 600; }
        
        .profile-header-card { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); margin-bottom: 30px; }
        
        .record-entry { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; transition: 0.3s; }
        .record-entry:hover { border-color: var(--primary); box-shadow: 0 10px 20px rgba(0,0,0,0.03); }
        .date-badge { width: 70px; height: 70px; background: #f8fafc; border-radius: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid #e2e8f0; }
        
        .prescription-box { background: #f0f9ff; border-left: 4px solid var(--primary); padding: 15px; border-radius: 8px; margin-top: 15px; }
        .advice-box { background: #fcfcfc; border: 1px dashed #e2e8f0; padding: 15px; border-radius: 8px; margin-top: 15px; }
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
        <a class="nav-link" href="appointments.php"><i class="bi bi-calendar-event me-3"></i> My Bookings</a>
        <a class="nav-link active" href="medical_records.php"><i class="bi bi-file-earmark-medical me-3"></i> Health Records</a>
        <a class="nav-link" href="ai_symptom_checker.php"><i class="bi bi-cpu me-3"></i> AI Disease Checker</a>
        <a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-3"></i> Billing</a>
        <div class="mt-auto px-4 pt-5"><hr></div>
        <a class="nav-link text-danger mb-4" href="../actions/logout.php"><i class="bi bi-power me-3"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <div class="profile-header-card d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-dark mb-1"><?= strtoupper(htmlspecialchars($patient['full_name'])) ?></h3>
            <div class="d-flex gap-3 align-items-center text-muted small">
                <span><i class="bi bi-person"></i> <?= $calculated_age ?> Years • <?= $patient['gender'] ?></span>
                <span>|</span>
                <span><i class="bi bi-telephone"></i> <?= htmlspecialchars($patient['contact_phone']) ?></span>
            </div>
        </div>
        <button class="btn btn-outline-primary rounded-pill px-4" onclick="window.print()">
            <i class="bi bi-printer me-2"></i> Print History
        </button>
    </div>

    <h4 class="fw-bold mb-4">Medical Timeline</h4>

    <?php if(empty($records)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="bi bi-folder2-open display-1 text-muted opacity-25"></i>
            <p class="mt-3 text-muted">No medical records available yet. Consultations appear here after completion.</p>
        </div>
    <?php else: ?>
        <?php foreach($records as $rec): ?>
            <div class="record-entry">
                <div class="row">
                    <div class="col-md-auto text-center">
                        <div class="date-badge mb-3">
                            <span class="fw-bold text-primary fs-4"><?= date('d', strtotime($rec['appointment_date'])) ?></span>
                            <span class="text-muted small text-uppercase fw-bold"><?= date('M', strtotime($rec['appointment_date'])) ?></span>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="fw-bold mb-0">Diagnosis: <?= htmlspecialchars($rec['diagnosis']) ?></h5>
                                <span class="text-muted small">Consulted with Dr. <?= htmlspecialchars($rec['d_name']) ?> (<?= $rec['expertise'] ?>)</span>
                            </div>
                            <a href="view_report.php?id=<?= $rec['appointment_id'] ?>" class="btn btn-sm btn-light border rounded-pill px-3">
                                <i class="bi bi-file-earmark-text me-1"></i> Full Report
                            </a>
                        </div>

                        <div class="row mt-3">
                            <div class="col-md-7">
                                <h6 class="fw-bold small text-muted text-uppercase mb-2">Prescribed Medications</h6>
                                <div class="prescription-box">
                                    <?php
                                    // Using consultation_id as the link to fetch prescriptions
                                    $stmt_presc = $pdo->prepare("SELECT * FROM Prescriptions WHERE consultation_id = ?");
                                    $stmt_presc->execute([$rec['consultation_id']]);
                                    $prescriptions = $stmt_presc->fetchAll();

                                    if($prescriptions): ?>
                                        <ul class="list-unstyled mb-0">
                                            <?php foreach($prescriptions as $p): ?>
                                                <li class="mb-2">
                                                    <i class="bi bi-capsule me-2 text-primary"></i>
                                                    <strong><?= htmlspecialchars($p['medicine_name']) ?></strong> 
                                                    <span class="badge bg-white text-dark border ms-2"><?= htmlspecialchars($p['dosage']) ?></span>
                                                    <span class="text-muted small ms-2">for <?= htmlspecialchars($p['duration'] ?: 'N/A') ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="small text-muted italic">No medications recorded for this visit.</span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="col-md-5">
                                <h6 class="fw-bold small text-muted text-uppercase mb-2">Doctor's Advice</h6>
                                <div class="advice-box">
                                    <p class="small text-muted mb-0">
                                        <i class="bi bi-quote fs-5 me-1 text-primary"></i>
                                        <?= $rec['advice'] ? htmlspecialchars($rec['advice']) : 'No specific dietary or follow-up advice provided.' ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>