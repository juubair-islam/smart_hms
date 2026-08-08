<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';
$patient_id = $_SESSION['user_id'];

try {
    // 1. Fetch Patient Profile
    $stmt = $pdo->prepare("SELECT * FROM Patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    $dob = new DateTime($patient['date_of_birth']);
    $calculated_age = (new DateTime())->diff($dob)->y;

    // 2. Fetch Appointments joined with Billing and Doctors
    // We check b.status to see if it's 'Paid'
    $stmt_apps = $pdo->prepare("SELECT a.*, d.full_name as d_name, d.expertise, 
                                b.status as bill_status, b.bill_id
                                FROM Appointments a 
                                JOIN Doctors d ON a.doctor_id = d.doctor_id 
                                LEFT JOIN Billing b ON a.appointment_id = b.appointment_id
                                WHERE a.patient_id = ? 
                                ORDER BY a.appointment_date DESC, a.created_at DESC");
    $stmt_apps->execute([$patient_id]);
    $my_appointments = $stmt_apps->fetchAll();

} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Bookings | Smart Health Care</title>
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
        .record-entry { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 20px; transition: 0.3s; }
        .date-badge { width: 75px; height: 75px; background: #f8fafc; border-radius: 18px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid #e2e8f0; }
        .status-pill { font-size: 0.75rem; font-weight: 700; padding: 5px 15px; border-radius: 20px; text-transform: uppercase; }
        .status-Scheduled { background: #ecfdf5; color: #065f46; border: 1px solid #a7f3d0; }
        .status-Completed { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
        .status-Cancelled { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        .doctor-info-box { background: #f8fafc; padding: 15px; border-radius: 12px; border: 1px solid #f1f5f9; }
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
    <?php if(isset($_SESSION['payment_success'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-4 shadow-sm border-0 mb-4" role="alert">
            <div class="d-flex align-items-center">
                <i class="bi bi-check-circle-fill fs-4 me-3"></i>
                <div>
                    <strong>Success!</strong> Payment processed and report unlocked.
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['payment_success']); ?>
    <?php endif; ?>

    <div class="profile-header-card d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-dark mb-1"><?= strtoupper(htmlspecialchars($patient['full_name'])) ?></h3>
            <div class="d-flex gap-3 align-items-center text-muted small">
                <span><i class="bi bi-person"></i> <?= $calculated_age ?> Years • <?= $patient['gender'] ?></span>
                <span>|</span>
                <span><i class="bi bi-telephone"></i> <?= htmlspecialchars($patient['contact_phone']) ?></span>
            </div>
        </div>
        <a href="find_doctor.php" class="btn btn-primary rounded-pill px-4 fw-bold">
            <i class="bi bi-plus-circle me-2"></i> New Booking
        </a>
    </div>

    <h4 class="fw-bold mb-4">My Appointment Timeline</h4>

    <?php if(empty($my_appointments)): ?>
        <div class="record-entry text-center py-5">
            <i class="bi bi-calendar-x display-4 text-muted opacity-25"></i>
            <p class="mt-3 text-muted">You have no appointment history yet.</p>
        </div>
    <?php else: ?>
        <?php foreach($my_appointments as $app): ?>
            <div class="record-entry">
                <div class="row align-items-center">
                    <div class="col-md-auto">
                        <div class="date-badge">
                            <span class="fw-bold text-primary fs-4"><?= date('d', strtotime($app['appointment_date'])) ?></span>
                            <span class="text-muted small text-uppercase fw-bold"><?= date('M', strtotime($app['appointment_date'])) ?></span>
                        </div>
                    </div>

                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <h5 class="fw-bold mb-1">Dr. <?= htmlspecialchars($app['d_name']) ?></h5>
                                <div class="small text-muted mb-2"><?= htmlspecialchars($app['expertise']) ?></div>
                            </div>
                            <span class="status-pill status-<?= $app['status'] ?>">
                                <?= $app['status'] ?>
                            </span>
                        </div>
                        
                        <div class="doctor-info-box mt-2">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <?php if($app['status'] == 'Completed'): ?>
                                        <?php if($app['bill_status'] == 'Paid'): ?>
                                            <span class="text-success fw-bold small"><i class="bi bi-unlock-fill me-1"></i> Full Consultation Report Unlocked</span>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold small"><i class="bi bi-lock-fill me-1"></i> Payment Required to view Prescription</span>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-muted small"><i class="bi bi-clock me-1"></i> Visit Scheduled</span>
                                    <?php endif; ?>
                                </div>
                                <div class="col-md-5 text-end">
                                    <?php if($app['status'] == 'Completed'): ?>
                                        <?php if($app['bill_status'] == 'Paid'): ?>
                                            <a href="view_report.php?id=<?= $app['appointment_id'] ?>" class="btn btn-sm btn-success rounded-pill px-3 fw-bold">
                                                <i class="bi bi-file-earmark-medical me-1"></i> View Prescription
                                            </a>
                                        <?php elseif($app['bill_id']): ?>
                                            <a href="make_payment.php?bill_id=<?= $app['bill_id'] ?>" class="btn btn-sm btn-warning rounded-pill px-3 fw-bold">
                                                <i class="bi bi-credit-card me-1"></i> Pay Now
                                            </a>
                                        <?php else: ?>
                                            <button class="btn btn-sm btn-secondary rounded-pill px-3 fw-bold" disabled>Processing Invoice...</button>
                                        <?php endif; ?>
                                    <?php elseif($app['status'] == 'Scheduled'): ?>
                                        <a href="../actions/cancel_appointment.php?id=<?= $app['appointment_id'] ?>" class="btn btn-sm btn-outline-danger border-0 fw-bold">Cancel</a>
                                    <?php endif; ?>
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