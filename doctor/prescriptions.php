<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header('Location: ../staff_access.php');
    exit;
}
require '../config/db.php';
$doctor_id = $_SESSION['user_id'];

// Get Search value
$search = trim($_GET['search'] ?? '');

try {
    // 1. Fetch Doctor Profile
    $stmt_doc = $pdo->prepare("SELECT * FROM Doctors WHERE doctor_id = ?");
    $stmt_doc->execute([$doctor_id]);
    $doctor = $stmt_doc->fetch();

    // 2. Fetch All Prescriptions issued by this doctor
    $query = "SELECT pr.*, p.full_name, p.patient_id, c.created_at as visit_date 
              FROM Prescriptions pr
              JOIN Consultations c ON pr.consultation_id = c.consultation_id
              JOIN Patients p ON c.patient_id = p.patient_id
              WHERE c.doctor_id = :doc_id";

    if (!empty($search)) {
        $query .= " AND (pr.medicine_name LIKE :search OR p.full_name LIKE :search)";
    }

    $query .= " ORDER BY c.created_at DESC";

    $stmt_pres = $pdo->prepare($query);
    $stmt_pres->bindValue(':doc_id', $doctor_id);
    if (!empty($search)) {
        $stmt_pres->bindValue(':search', "%$search%");
    }
    $stmt_pres->execute();
    $prescriptions = $stmt_pres->fetchAll();

} catch (PDOException $e) {
    error_log($e->getMessage());
    $error_msg = "Error loading prescription records.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prescription Log | Smart HMS</title>
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

        /* Sidebar - Same as Dashboard */
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

        /* Banner */
        .profile-banner { background: white; border: 1px solid #e2e8f0; border-radius: 24px; padding: 30px; margin-bottom: 30px; }
        .doctor-avatar { width: 90px; height: 90px; object-fit: cover; border-radius: 20px; border: 4px solid var(--medical-light); }

        .glass-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 20px;
            padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        /* Table UI */
        .medicine-tag { background: #e0f2fe; color: #0369a1; padding: 4px 12px; border-radius: 6px; font-weight: 700; font-size: 0.85rem; }
        .dosage-badge { background: #f1f5f9; color: #475569; padding: 4px 10px; border-radius: 6px; font-size: 0.75rem; border: 1px solid #e2e8f0; }
        
        .search-bar {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 12px 15px 12px 45px; font-weight: 500; width: 100%;
        }
        .search-container { position: relative; }
        .search-icon { position: absolute; left: 15px; top: 14px; color: #64748b; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand-area">
        <div class="d-flex align-items-center">
            <i class="bi bi-capsule-pill text-primary fs-2 me-3"></i>
            <div>
                <div class="brand-title text-primary">Smart HCMS</div>
            </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill me-3"></i> Dashboard</a>
        <a href="appointments.php" class="nav-link"><i class="bi bi-calendar2-week me-3"></i> Appointments</a>
        <a href="patients.php" class="nav-link"><i class="bi bi-person-lines-fill me-3"></i> Patient Records</a>
        <a href="prescriptions.php" class="nav-link active"><i class="bi bi-capsule me-3"></i> Prescriptions</a>
        
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
                <div class="small text-primary fw-bold"><?= $doctor['expertise'] ?> • Prescription Management</div>
            </div>
        </div>
        <div class="text-end">
            <p class="mb-0 text-muted small fw-medium"><?= date('l, jS F Y') ?></p>
            <span class="badge bg-primary-subtle text-primary border border-primary-subtle mt-2">Medication Records</span>
        </div>
    </div>

    <div class="glass-card mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-10">
                <div class="search-container">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" class="search-bar" placeholder="Search by Medicine Name or Patient Name..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Search</button>
            </div>
        </form>
    </div>

    <div class="glass-card">
        <h5 class="fw-bold mb-4">Master Medication Log</h5>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 text-muted small px-3">DATE</th>
                        <th class="border-0 text-muted small">PATIENT</th>
                        <th class="border-0 text-muted small">MEDICINE NAME</th>
                        <th class="border-0 text-muted small">DOSAGE</th>
                        <th class="border-0 text-muted small">DURATION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($prescriptions)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No prescriptions found.</td></tr>
                    <?php else: ?>
                        <?php foreach($prescriptions as $p): ?>
                        <tr>
                            <td class="px-3 small fw-bold text-muted"><?= date('d M, Y', strtotime($p['visit_date'])) ?></td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($p['full_name']) ?></div>
                                <small class="text-muted">ID: #P-<?= $p['patient_id'] ?></small>
                            </td>
                            <td><span class="medicine-tag"><?= htmlspecialchars($p['medicine_name']) ?></span></td>
                            <td><span class="dosage-badge"><?= htmlspecialchars($p['dosage']) ?></span></td>
                            <td><span class="text-muted small fw-medium"><?= htmlspecialchars($p['duration']) ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

</body>
</html>