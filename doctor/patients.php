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
    // 1. Fetch Doctor Profile (For Banner)
    $stmt = $pdo->prepare("SELECT * FROM Doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    // 2. Fetch Unique Patients treated by this doctor
    // We group by patient_id to show a clean list of individual people
    $query = "SELECT p.*, 
                     MAX(c.created_at) as last_visit, 
                     COUNT(c.consultation_id) as total_visits
              FROM Patients p
              JOIN Consultations c ON p.patient_id = c.patient_id
              WHERE c.doctor_id = :doc_id";

    if (!empty($search)) {
        $query .= " AND (p.full_name LIKE :search OR p.patient_id LIKE :search OR p.contact_phone LIKE :search)";
    }

    $query .= " GROUP BY p.patient_id ORDER BY last_visit DESC";

    $stmt_patients = $pdo->prepare($query);
    $stmt_patients->bindValue(':doc_id', $doctor_id);
    if (!empty($search)) {
        $stmt_patients->bindValue(':search', "%$search%");
    }
    $stmt_patients->execute();
    $patients = $stmt_patients->fetchAll();

} catch (PDOException $e) {
    error_log("Patients Page Error: " . $e->getMessage());
    $error_msg = "Error loading patient records.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Records | Smart HMS</title>
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

        /* Sidebar Style */
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

        /* Banner & Card */
        .profile-banner { background: white; border: 1px solid #e2e8f0; border-radius: 24px; padding: 30px; margin-bottom: 30px; }
        .doctor-avatar { width: 90px; height: 90px; object-fit: cover; border-radius: 20px; border: 4px solid var(--medical-light); }
        .status-pill { font-size: 0.75rem; padding: 6px 16px; border-radius: 50px; background: #f1f5f9; font-weight: 600; color: #475569; }

        .glass-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 20px;
            padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02);
        }

        /* Search Bar */
        .search-container { position: relative; }
        .search-bar {
            background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 12px;
            padding: 12px 15px 12px 45px; font-weight: 500; width: 100%;
        }
        .search-icon { position: absolute; left: 15px; top: 14px; color: #64748b; }

        /* Buttons */
        .btn-view {
            background: var(--medical-light); color: var(--medical-blue);
            border: 1px solid #e0f2fe; border-radius: 8px; font-weight: 700;
            padding: 6px 15px; font-size: 0.8rem; transition: 0.3s;
        }
        .btn-view:hover { background: var(--medical-blue); color: white; }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand-area">
        <div class="d-flex align-items-center">
            <i class="bi bi-person-heart text-primary fs-2 me-3"></i>
            <div>
                <div class="brand-title text-primary">Smart HCMS</div>
            </div>
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

    <div class="glass-card mb-4">
        <form method="GET" class="row g-3">
            <div class="col-md-9">
                <div class="search-container">
                    <i class="bi bi-search search-icon"></i>
                    <input type="text" name="search" class="search-bar" placeholder="Search by Patient Name, ID, or Phone..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 rounded-3 py-2 fw-bold">Search Records</button>
            </div>
        </form>
    </div>

    <div class="glass-card">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0">My Patient Database</h5>
            <span class="badge bg-light text-dark border"><?= count($patients) ?> Registered Patients</span>
        </div>
        
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="bg-light">
                    <tr>
                        <th class="border-0 text-muted small px-3">PATIENT NAME</th>
                        <th class="border-0 text-muted small">CONTACT</th>
                        <th class="border-0 text-muted small text-center">VISITS</th>
                        <th class="border-0 text-muted small">LAST CONSULTATION</th>
                        <th class="border-0 text-muted small text-end px-3">ACTION</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if(empty($patients)): ?>
                        <tr><td colspan="5" class="text-center py-5 text-muted">No patient records found.</td></tr>
                    <?php else: ?>
                        <?php foreach($patients as $p): ?>
                        <tr>
                            <td class="px-3">
                                <div class="d-flex align-items-center">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 40px; height: 40px; font-weight: 700;">
                                        <?= substr($p['full_name'], 0, 1) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($p['full_name']) ?></div>
                                        <small class="text-muted">ID: #P-<?= $p['patient_id'] ?> | <?= $p['gender'] ?></small>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-medium"><?= htmlspecialchars($p['contact_phone']) ?></div>
                                <small class="text-muted"><?= htmlspecialchars($p['email'] ?: 'No Email') ?></small>
                            </td>
                            <td class="text-center">
                                <span class="badge rounded-pill bg-primary-subtle text-primary fw-bold px-3"><?= $p['total_visits'] ?></span>
                            </td>
                            <td>
                                <div class="small fw-bold"><?= date('d M, Y', strtotime($p['last_visit'])) ?></div>
                                <small class="text-muted"><?= date('h:i A', strtotime($p['last_visit'])) ?></small>
                            </td>
                            <td class="text-end px-3">
                                <a href="view_history.php?patient_id=<?= $p['patient_id'] ?>" class="btn-view">
                                    <i class="bi bi-eye me-1"></i> View History
                                </a>
                            </td>
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