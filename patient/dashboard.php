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
    $stmt = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    $dob = new DateTime($patient['date_of_birth']);
    $calculated_age = (new DateTime())->diff($dob)->y;

    // 2. Fetch Upcoming Appointment (The 'Next Visit' Entry)
    $stmt_app = $pdo->prepare("SELECT a.*, d.full_name as d_name, d.expertise 
                               FROM appointments a 
                               JOIN doctors d ON a.doctor_id = d.doctor_id 
                               WHERE a.patient_id = ? AND a.appointment_date >= CURDATE() 
                               AND a.status = 'Scheduled'
                               ORDER BY a.appointment_date ASC LIMIT 1");
    $stmt_app->execute([$patient_id]);
    $next_app = $stmt_app->fetch();

    // 3. Fetch AI Reminders
    $stmt_remind = $pdo->prepare("SELECT message FROM reminders WHERE patient_id = ? AND status = 'Active'");
    $stmt_remind->execute([$patient_id]);
    $ai_reminders = $stmt_remind->fetchAll();

    // 4. Fetch My Blood Requests & Donor Responses
    // Added req_status to track if the request was flipping to 'Resolved'
    $stmt_blood = $pdo->prepare("
        SELECT er.request_id, er.blood_group, er.urgency_level, er.status as req_status, er.created_at,
               dr.status as donor_status, p.full_name as donor_name, p.contact_phone as donor_phone
        FROM emergency_requests er
        LEFT JOIN donor_responses dr ON er.request_id = dr.request_id
        LEFT JOIN patients p ON dr.donor_id = p.patient_id
        WHERE er.requester_id = ? AND er.request_type = 'Blood'
        ORDER BY er.created_at DESC LIMIT 5
    ");
    $stmt_blood->execute([$patient_id]);
    $my_blood_requests = $stmt_blood->fetchAll();

} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Dashboard | Smart Health Care Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #0077b6; --bg: #f4f7fe; --sidebar: #ffffff; }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; }
        
        /* Sidebar (Unified) */
        .sidebar { background: var(--sidebar); min-height: 100vh; border-right: 1px solid #e2e8f0; position: fixed; width: 280px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; }
        .brand-box { padding: 30px 25px; line-height: 1.2; }
        .brand-text-top { font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; }
        .brand-text-bottom { font-size: 0.9rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }

        .nav-link { color: #64748b; padding: 14px 20px; border-radius: 12px; margin: 4px 20px; display: flex; align-items: center; transition: 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: #eff6ff; color: var(--primary); font-weight: 600; }
        
        /* Profile Card (Unified) */
        .profile-header-card { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); margin-bottom: 30px; }
        
        /* Entry Styling (Unified from Medical Records) */
        .record-entry { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; transition: 0.3s; }
        .date-badge { width: 70px; height: 70px; background: #f8fafc; border-radius: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid #e2e8f0; }
        
        .prescription-box { background: #f0f9ff; border-left: 4px solid var(--primary); padding: 15px; border-radius: 8px; margin-top: 15px; }
        .ai-score-pill { font-size: 0.75rem; font-weight: 700; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 4px 12px; border-radius: 20px; }
        
        /* Emergency Highlight */
        .emergency-box { background: #fef2f2; border-left: 4px solid #ef4444; padding: 15px; border-radius: 8px; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand-box">
        <span class="brand-text-top">Smart Health Care</span>
        <span class="brand-text-bottom">Management System</span>
    </div>
    <nav class="nav flex-column mt-4">
        <a class="nav-link active" href="dashboard.php"><i class="bi bi-house-heart me-3"></i> Overview</a>
        <a class="nav-link" href="appointments.php"><i class="bi bi-calendar-event me-3"></i> My Bookings</a>
        <a class="nav-link" href="medical_records.php"><i class="bi bi-file-earmark-medical me-3"></i> Health Records</a>
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
        <div class="text-end">
             <span class="text-muted small d-block">System Status</span>
             <span class="badge bg-success-subtle text-success rounded-pill">Patient Portal Active</span>
        </div>
    </div>

    <h4 class="fw-bold mb-4">Patient Overview</h4>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 fw-bold border-success border-opacity-25" role="alert">
            <i class="bi bi-broadcast text-success fs-5 me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="record-entry">
        <div class="row align-items-center">
            <div class="col-md-auto">
                <div class="date-badge">
                    <?php if($next_app): ?>
                        <span class="fw-bold text-primary fs-4"><?= date('d', strtotime($next_app['appointment_date'])) ?></span>
                        <span class="text-muted small text-uppercase fw-bold"><?= date('M', strtotime($next_app['appointment_date'])) ?></span>
                    <?php else: ?>
                        <i class="bi bi-calendar-x fs-3 text-muted"></i>
                    <?php endif; ?>
                </div>
            </div>
            <div class="col">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="fw-bold mb-0">Upcoming Appointment</h5>
                        <?php if($next_app): ?>
                            <span class="text-muted small">Confirmed with Dr. <?= htmlspecialchars($next_app['d_name']) ?> (<?= $next_app['expertise'] ?>)</span>
                        <?php else: ?>
                            <span class="text-muted small">No upcoming visits scheduled.</span>
                        <?php endif; ?>
                    </div>
                    <?php if($next_app): ?>
                        <span class="ai-score-pill" style="background: #ecfdf5; color: #065f46; border-color: #a7f3d0;">
                            <i class="bi bi-clock me-1"></i> Time: <?= date('h:i A', strtotime($next_app['appointment_date'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <?php if(!$next_app): ?>
                    <div class="mt-3">
                        <a href="appointments.php" class="btn btn-sm btn-primary rounded-pill px-4">Book Now</a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="record-entry">
        <div class="row">
            <div class="col-md-auto text-center">
                <div class="date-badge" style="background: #fdf2f7; border-color: #fbcfe8;">
                    <i class="bi bi-robot fs-3" style="color: #db2777;"></i>
                </div>
            </div>
            <div class="col">
                <h5 class="fw-bold mb-2">AI Care Recommendations</h5>
                <div class="prescription-box" style="border-left-color: #db2777; background: #fff1f2;">
                    <?php if($ai_reminders): ?>
                        <ul class="list-unstyled mb-0">
                            <?php foreach($ai_reminders as $rem): ?>
                                <li class="mb-2">
                                    <i class="bi bi-stars me-2" style="color: #db2777;"></i>
                                    <strong>Recommendation:</strong> <?= htmlspecialchars($rem['message']) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <span class="small text-muted">AI is analyzing your recent records. No new alerts.</span>
                    <?php endif; ?>
                </div>
                <div class="mt-3">
                    <a href="ai_symptom_checker.php" class="btn btn-sm btn-outline-dark rounded-pill px-4">Run Symptom Scan</a>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4 mt-1">
        <div class="col-md-6">
            <div class="record-entry h-100">
                <h6 class="fw-bold small text-muted text-uppercase mb-3">Emergency Contact</h6>
                <div class="emergency-box">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-telephone-fill text-danger fs-4 me-3"></i>
                        <div>
                            <div class="fw-bold"><?= htmlspecialchars($patient['emergency_contact_name'] ?? 'Hospital Line') ?></div>
                            <div class="text-danger fw-bold"><?= htmlspecialchars($patient['emergency_contact_number'] ?? '999') ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="record-entry h-100">
                <h6 class="fw-bold small text-muted text-uppercase mb-3">Quick Links</h6>
                <div class="d-grid gap-2">
                    <a href="medical_records.php" class="btn btn-light border text-start btn-sm py-2 px-3 rounded-3">
                        <i class="bi bi-file-earmark-text me-2 text-primary"></i> Last Prescription
                    </a>
                    <a href="billing.php" class="btn btn-light border text-start btn-sm py-2 px-3 rounded-3">
                        <i class="bi bi-receipt me-2 text-primary"></i> Pending Invoices
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="record-entry mt-4" style="border-left: 4px solid #ef4444; background: #fffcfc;">
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
            <h5 class="fw-bold mb-0 text-danger"><i class="bi bi-droplet-fill me-2"></i>Blood Emergency Network</h5>
            <div class="d-flex gap-2">
                <a href="../live_map.php" class="btn btn-outline-danger btn-sm rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-map me-1"></i> Find Donors on Map
                </a>
                <a href="../request_blood.php" class="btn btn-danger btn-sm rounded-pill fw-bold shadow-sm">
                    <i class="bi bi-broadcast me-1"></i> Quick Request Blood
                </a>
            </div>
        </div>

        <h6 class="fw-bold small text-muted text-uppercase mb-3">My Recent Blood Requests</h6>
        
        <?php if(empty($my_blood_requests)): ?>
            <div class="bg-white p-3 rounded-3 border text-center text-muted small shadow-sm">
                <i class="bi bi-shield-check fs-4 d-block mb-1 text-success"></i>
                No active blood requests. You are safe!
            </div>
        <?php else: ?>
            <div class="table-responsive bg-white border rounded-3 shadow-sm p-2">
                <table class="table table-borderless align-middle mb-0">
                    <thead class="text-muted small text-uppercase border-bottom">
                        <tr>
                            <th>Blood Group</th>
                            <th>Urgency</th>
                            <th>Status</th>
                            <th>Donor Response</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($my_blood_requests as $req): ?>
                        <tr class="border-bottom border-light">
                            <td class="fw-bold text-danger fs-5"><?= htmlspecialchars($req['blood_group']) ?></td>
                            
                            <td>
                                <?php if($req['req_status'] == 'Resolved'): ?>
                                    <span class="badge bg-success rounded-pill px-3 py-2 shadow-sm"><i class="bi bi-check2-circle me-1"></i> Blood Secured</span>
                                <?php else: ?>
                                    <span class="badge bg-<?= $req['urgency_level'] == 'Critical' ? 'danger' : 'warning text-dark' ?> rounded-pill px-3 py-2">
                                        <?= $req['urgency_level'] ?>
                                    </span>
                                <?php endif; ?>
                            </td>
                            
                            <td>
                                <span class="badge bg-<?= $req['req_status'] == 'Resolved' ? 'success' : 'secondary' ?> bg-opacity-10 text-<?= $req['req_status'] == 'Resolved' ? 'success' : 'secondary' ?> border px-3 py-2 rounded-pill">
                                    <?= $req['req_status'] == 'Resolved' ? 'Done' : 'Active' ?>
                                </span>
                            </td>
                            
                            <td>
                                <?php if($req['donor_status']): ?>
                                    <div class="bg-success bg-opacity-10 text-success p-2 rounded-3 border border-success border-opacity-25">
                                        <div class="fw-bold small"><i class="bi bi-check-circle-fill me-1"></i> Accepted by <?= htmlspecialchars($req['donor_name']) ?></div>
                                        <div class="small mt-1"><i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($req['donor_phone']) ?></div>
                                    </div>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border text-start p-2">
                                        <i class="bi bi-hourglass-split text-warning me-1"></i> Waiting for Donors...<br>
                                        <small class="fw-normal">Alert sent to network.</small>
                                    </span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>