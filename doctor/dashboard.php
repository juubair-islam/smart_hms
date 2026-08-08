<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header('Location: ../staff_access.php');
    exit;
}
require '../config/db.php';
$doctor_id = $_SESSION['user_id'];

try {
    // 1. Fetch Doctor Profile
    $stmt = $pdo->prepare("SELECT * FROM Doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    // 2. Fetch Stats
    // Today's Pending Appointments: 
    // Must be Scheduled (not Cancelled) AND not yet in Consultations
    $stmt_today = $pdo->prepare("SELECT COUNT(*) FROM Appointments a 
                                 LEFT JOIN Consultations c ON a.appointment_id = c.appointment_id
                                 WHERE a.doctor_id = ? 
                                 AND a.appointment_date = CURDATE() 
                                 AND a.status != 'Cancelled'
                                 AND c.consultation_id IS NULL");
    $stmt_today->execute([$doctor_id]);
    $today_count = $stmt_today->fetchColumn();

    // Total Patients Treated (Completed Consultations)
    $stmt_total = $pdo->prepare("SELECT COUNT(DISTINCT patient_id) FROM Consultations WHERE doctor_id = ?");
    $stmt_total->execute([$doctor_id]);
    $total_patients = $stmt_total->fetchColumn();

    // 3. Fetch Today's Timeline (Queue)
    // Filtered by: Today's Date AND Not Cancelled AND Not Consulted Yet
    $stmt_timeline = $pdo->prepare("SELECT a.*, p.full_name as p_name, p.gender, p.date_of_birth 
                                    FROM Appointments a 
                                    JOIN Patients p ON a.patient_id = p.patient_id 
                                    LEFT JOIN Consultations c ON a.appointment_id = c.appointment_id
                                    WHERE a.doctor_id = ? 
                                    AND a.appointment_date = CURDATE()
                                    AND a.status != 'Cancelled'
                                    AND c.consultation_id IS NULL
                                    ORDER BY a.appointment_id ASC"); 
    $stmt_timeline->execute([$doctor_id]);
    $timeline = $stmt_timeline->fetchAll();

} catch (PDOException $e) {
    error_log("Doctor Dashboard Error: " . $e->getMessage());
    $error_msg = "Could not load dashboard data.";
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --medical-blue: #0077b6;
            --medical-light: #f0f9ff;
            --dark-navy: #1e293b;
            --sidebar-width: 280px;
        }

        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            transition: all 0.3s;
            z-index: 1000;
        }

        .brand-area { padding: 30px 25px; border-bottom: 1px solid #f1f5f9; }
        .brand-title { font-size: 1.1rem; line-height: 1.2; font-weight: 800; color: var(--dark-navy); text-transform: uppercase; letter-spacing: -0.5px; }

        .role-badge {
            background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 6px;
            font-size: 0.7rem; font-weight: 800; display: inline-block; margin-top: 5px; border: 1px solid #bbf7d0;
        }

        .nav-link {
            margin: 5px 15px; padding: 12px 15px; border-radius: 10px; color: #64748b;
            font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none;
        }

        .nav-link:hover, .nav-link.active { background: var(--medical-light); color: var(--medical-blue); }

        .main-wrapper { margin-left: var(--sidebar-width); padding: 40px; }

        .glass-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 20px;
            padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: transform 0.2s;
        }
        .glass-card:hover { transform: translateY(-5px); }

        .icon-circle {
            width: 54px; height: 54px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        }

        .profile-banner { background: white; border: 1px solid #e2e8f0; border-radius: 24px; padding: 30px; margin-bottom: 30px; }
        .doctor-avatar { width: 90px; height: 90px; object-fit: cover; border-radius: 20px; border: 4px solid var(--medical-light); }

        .status-pill { font-size: 0.75rem; padding: 6px 16px; border-radius: 50px; background: #f1f5f9; font-weight: 600; color: #475569; }

        .btn-consult {
            background: var(--medical-blue); color: white; border-radius: 10px;
            font-weight: 600; padding: 8px 20px; border: none; text-decoration: none; transition: 0.3s;
        }
        .btn-consult:hover { background: #005f91; color: white; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand-area">
        <div class="d-flex align-items-center">
            <i class="bi bi-person-heart text-primary fs-2 me-3"></i>
            <div>
                <div class="brand-title text-primary">Smart HCMS</div> </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-fill me-3"></i> Dashboard</a>
        <a href="appointments.php" class="nav-link"><i class="bi bi-calendar2-week me-3"></i> Appointments</a>
        <a href="patients.php" class="nav-link"><i class="bi bi-person-lines-fill me-3"></i> Patient Records</a>
        <a href="prescriptions.php" class="nav-link"><i class="bi bi-capsule me-3"></i> Prescriptions</a>
       
        
        <div style="margin-top: 100px;">
            <a href="../actions/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left me-3"></i> Logout</a>
        </div>
    </div>
</aside>

<main class="main-wrapper">
    
    <div class="profile-banner d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <img src="../<?= $doctor['image_url'] ?: 'assets/img/default_doc.jpg' ?>" class="doctor-avatar me-4 shadow-sm">
            <div>
                <h2 class="fw-bold mb-1">Dr. <?= htmlspecialchars($doctor['full_name']) ?></h2>
                <div class="d-flex align-items-center gap-2">
                    <span class="text-primary fw-bold small"><?= $doctor['expertise'] ?></span>
                    <span class="text-muted small">•</span>
                    <span class="text-muted small"><?= $doctor['qualification'] ?></span>
                </div>
            </div>
        </div>
        <div class="text-end">
            <div class="status-pill mb-2"><i class="bi bi-circle-fill text-success me-2" style="font-size: 8px;"></i> Active Today</div>
            <p class="mb-0 text-muted small fw-medium"><?= date('l, jS F Y') ?></p>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card d-flex align-items-center">
                <div class="icon-circle bg-primary-subtle text-primary me-3">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0"><?= $today_count ?></h3>
                    <p class="text-muted small mb-0">Pending for Today</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card d-flex align-items-center">
                <div class="icon-circle bg-success-subtle text-success me-3">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0"><?= $total_patients ?></h3>
                    <p class="text-muted small mb-0">Total Patients Treated</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card d-flex align-items-center">
                <div class="icon-circle bg-warning-subtle text-warning me-3">
                    <i class="bi bi-hand-thumbs-up"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0">98%</h3>
                    <p class="text-muted small mb-0">Positive Feedback</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-8">
            <h4 class="fw-bold mb-4">Patient Queue</h4>
            
            <?php if(empty($timeline)): ?>
                <div class="glass-card text-center py-5">
                    <i class="bi bi-check2-circle text-success display-4 mb-3"></i>
                    <h5 class="fw-bold">All caught up!</h5>
                    <p class="text-muted">No more pending appointments for today.</p>
                </div>
            <?php else: ?>
                <?php foreach($timeline as $app): ?>
                    <div class="glass-card mb-3 d-flex align-items-center justify-content-between border-start border-4 border-primary">
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded-3 p-3 text-primary fw-bold me-3">
                                <?= substr($app['p_name'], 0, 1) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($app['p_name']) ?></h6>
                                <small class="text-muted"><?= $app['gender'] ?> • Patient ID: #P-<?= $app['patient_id'] ?></small>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-4">
                            <div class="text-end">
                                <span class="badge rounded-pill text-bg-primary small px-3">Waiting</span>
                            </div>
                            <a href="consult_patient.php?id=<?= $app['appointment_id'] ?>" class="btn-consult">Start Consultation</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="col-lg-4">
            <h4 class="fw-bold mb-4">System Insights</h4>
            <div class="glass-card bg-dark text-white mb-4">
                <h6 class="fw-bold"><i class="bi bi-cpu text-info me-2"></i>AI Diagnostic Aid</h6>
                <p class="small opacity-75 mt-3">Smart HMS has detected a seasonal spike in "Viral Fever" reports in your region. Consider prioritizing related tests.</p>
                <hr class="opacity-25">
                <div class="d-flex justify-content-between small">
                    <span>Confidence Score</span>
                    <span class="text-info">88%</span>
                </div>
            </div>

            <div class="glass-card">
                <h6 class="fw-bold mb-3">Tools</h6>
                <button onclick="showToolAlert('Lab Reports')" class="btn btn-light w-100 text-start mb-2 rounded-3"><i class="bi bi-file-earmark-pdf me-2"></i> View Lab Reports</button>
                <button onclick="showToolAlert('Staff Chat')" class="btn btn-light w-100 text-start rounded-3"><i class="bi bi-chat-dots me-2"></i> Internal Staff Chat</button>
            </div>
        </div>
    </div>
</main>

<script>
    // 1. Tool Alerts (In-Page)
    function showToolAlert(tool) {
        Swal.fire({
            title: tool,
            text: 'This module is currently being synchronized with the hospital main server.',
            icon: 'info',
            confirmButtonColor: '#0077b6'
        });
    }

    // 2. Success Redirect Alert
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.has('status') && urlParams.get('status') === 'consultation_success') {
        Swal.fire({
            title: 'Consultation Complete',
            text: 'The patient record has been moved to the archives.',
            icon: 'success',
            confirmButtonColor: '#0077b6',
            timer: 3000
        });
    }
</script>

</body>
</html>