<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header('Location: ../staff_access.php');
    exit;
}
require '../config/db.php';
$doctor_id = $_SESSION['user_id'];
$patient_id = $_GET['patient_id'] ?? null;

if (!$patient_id) {
    header('Location: patients.php');
    exit;
}

try {
    // 1. Fetch Doctor Profile (For the Banner)
    $stmt_doc = $pdo->prepare("SELECT * FROM Doctors WHERE doctor_id = ?");
    $stmt_doc->execute([$doctor_id]);
    $doctor = $stmt_doc->fetch();

    // 2. Fetch Patient Info
    $stmt_p = $pdo->prepare("SELECT * FROM Patients WHERE patient_id = ?");
    $stmt_p->execute([$patient_id]);
    $patient = $stmt_p->fetch();

    // 3. Fetch All Consultations for this patient with this doctor
    $stmt_c = $pdo->prepare("SELECT * FROM Consultations WHERE patient_id = ? AND doctor_id = ? ORDER BY created_at DESC");
    $stmt_c->execute([$patient_id, $doctor_id]);
    $history = $stmt_c->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    die("Error loading history.");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clinical History | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --medical-blue: #0077b6;
            --medical-light: #f0f9ff;
            --dark-navy: #1e293b;
            --sidebar-width: 280px;
        }

        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; }

        /* Sidebar Style - Match Dashboard */
        .sidebar {
            width: var(--sidebar-width); height: 100vh; position: fixed;
            background: #ffffff; border-right: 1px solid #e2e8f0; z-index: 1000;
        }
        .brand-area { padding: 30px 25px; border-bottom: 1px solid #f1f5f9; }
        .brand-title { font-size: 1.1rem; line-height: 1.2; font-weight: 800; color: var(--dark-navy); text-transform: uppercase; }
        .nav-link {
            margin: 5px 15px; padding: 12px 15px; border-radius: 10px; color: #64748b;
            font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none;
        }
        .nav-link:hover, .nav-link.active { background: var(--medical-light); color: var(--medical-blue); }

        .main-wrapper { margin-left: var(--sidebar-width); padding: 40px; }

        /* Doctor Profile Banner */
        .profile-banner { background: white; border: 1px solid #e2e8f0; border-radius: 24px; padding: 30px; margin-bottom: 30px; }
        .doctor-avatar { width: 90px; height: 90px; object-fit: cover; border-radius: 20px; border: 4px solid var(--medical-light); }
        .status-pill { font-size: 0.75rem; padding: 6px 16px; border-radius: 50px; background: #f1f5f9; font-weight: 600; color: #475569; }

        /* Patient Context Card */
        .patient-header-card {
            background: linear-gradient(135deg, #0077b6 0%, #00b4d8 100%);
            color: white; border-radius: 20px; padding: 25px; margin-bottom: 30px;
        }

        /* Timeline UI */
        .timeline { border-left: 3px solid #e2e8f0; margin-left: 20px; padding-left: 30px; position: relative; }
        .timeline-item { position: relative; margin-bottom: 40px; }
        .timeline-item::before {
            content: ""; position: absolute; left: -38.5px; top: 0;
            width: 15px; height: 15px; background: var(--medical-blue);
            border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 3px var(--medical-light);
        }

        .glass-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 20px;
            padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        .section-label { font-size: 0.7rem; font-weight: 800; text-transform: uppercase; color: #94a3b8; letter-spacing: 0.5px; margin-bottom: 8px; display: block; }
        .date-badge { background: var(--medical-light); color: var(--medical-blue); font-weight: 700; padding: 5px 15px; border-radius: 8px; font-size: 0.85rem; }
        .prescription-box { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px; padding: 15px; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand-area">
        <div class="d-flex align-items-center">
            <i class="bi bi-person-heart text-primary fs-2 me-3"></i>
            <div>
               <div class="brand-title text-primary">Smart HCMS</div>   </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill me-3"></i> Dashboard</a>
        <a href="appointments.php" class="nav-link"><i class="bi bi-calendar2-week me-3"></i> Appointments</a>
        <a href="patients.php" class="nav-link active"><i class="bi bi-person-lines-fill me-3"></i> Patient Records</a>
        <a href="prescriptions.php" class="nav-link"><i class="bi bi-capsule me-3"></i> Prescriptions</a>
        
        <div style="margin-top: 150px;">
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

    <div class="patient-header-card shadow-sm d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <div class="bg-white text-primary rounded-circle d-flex align-items-center justify-content-center me-4" style="width: 60px; height: 60px; font-weight: 800; font-size: 1.4rem;">
                <?= substr($patient['full_name'], 0, 1) ?>
            </div>
            <div>
                <h4 class="fw-bold mb-1"><?= htmlspecialchars($patient['full_name']) ?></h4>
                <div class="small opacity-75">
                    ID: #P-<?= $patient['patient_id'] ?> | <?= $patient['gender'] ?> | <?= $patient['blood_group'] ?>
                </div>
            </div>
        </div>
        <a href="patients.php" class="btn btn-light btn-sm rounded-pill px-3 fw-bold">
            <i class="bi bi-arrow-left me-1"></i> Back to List
        </a>
    </div>

    <div class="row">
        <div class="col-lg-12">
            <h5 class="fw-bold mb-4 px-2"><i class="bi bi-journal-medical me-2 text-primary"></i>Clinical Timeline</h5>

            <?php if(empty($history)): ?>
                <div class="glass-card text-center py-5">
                    <i class="bi bi-folder2-open display-1 text-light"></i>
                    <p class="text-muted mt-3">No medical history recorded for this patient.</p>
                </div>
            <?php else: ?>
                <div class="timeline">
                    <?php foreach($history as $record): ?>
                        <div class="timeline-item">
                            <div class="mb-3">
                                <span class="date-badge"><?= date('D, d M Y', strtotime($record['created_at'])) ?></span>
                            </div>
                            
                            <div class="glass-card border-0 shadow-sm">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <span class="section-label">Reported Symptoms</span>
                                        <p class="fw-medium text-dark"><?= nl2br(htmlspecialchars($record['symptoms'])) ?></p>
                                    </div>
                                    <div class="col-md-6">
                                        <span class="section-label" style="color: #e63946;">Final Diagnosis</span>
                                        <p class="fw-bold text-danger"><?= nl2br(htmlspecialchars($record['diagnosis'])) ?></p>
                                    </div>
                                    <div class="col-12 border-top pt-3">
                                        <span class="section-label" style="color: #2a9d8f;">Doctor's Advice</span>
                                        <p class="small text-muted mb-3"><?= nl2br(htmlspecialchars($record['advice'])) ?></p>
                                        
                                        <span class="section-label">Prescribed Medications</span>
                                        <div class="prescription-box">
                                            <?php
                                            $stmt_m = $pdo->prepare("SELECT * FROM Prescriptions WHERE consultation_id = ?");
                                            $stmt_m->execute([$record['consultation_id']]);
                                            $meds = $stmt_m->fetchAll();
                                            
                                            if($meds):
                                                foreach($meds as $m): ?>
                                                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom border-white">
                                                        <div>
                                                            <span class="fw-bold text-primary small"><?= $m['medicine_name'] ?></span>
                                                            <span class="text-muted mx-2">•</span>
                                                            <span class="small fw-medium"><?= $m['dosage'] ?></span>
                                                        </div>
                                                        <span class="badge bg-white text-dark border small fw-bold"><?= $m['duration'] ?></span>
                                                    </div>
                                                <?php endforeach;
                                            else:
                                                echo "<span class='text-muted small'>No medicines prescribed during this visit.</span>";
                                            endif;
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>