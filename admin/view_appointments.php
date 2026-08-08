<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

// Initialize to avoid the previous 'Undefined variable' error
$appointments = [];

try {
    // Joining tables to get a complete overview
    $query = "SELECT a.*, p.full_name as patient_name, d.full_name as doctor_name, d.expertise 
              FROM Appointments a
              JOIN Patients p ON a.patient_id = p.patient_id
              JOIN Doctors d ON a.doctor_id = d.doctor_id
              ORDER BY a.appointment_date DESC";
    
    $stmt = $pdo->query($query);
    $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_msg = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointment Logs | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-blue: #0077b6;
            --light-bg: #f8f9fa;
            --nav-dark: #1e293b;
        }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', sans-serif; }
        
        .header-top {
            background: #fff;
            padding: 12px 30px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .sub-nav {
            background: var(--nav-dark);
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }
        .sub-nav .nav-link {
            color: #94a3b8;
            padding: 15px 20px;
            font-weight: 500;
            transition: all 0.3s;
        }
        .sub-nav .nav-link:hover, .sub-nav .nav-link.active {
            color: #fff;
            background: rgba(255,255,255,0.05);
            border-bottom: 3px solid var(--primary-blue);
        }

        .search-area {
            background: #fff;
            border-radius: 12px;
            padding: 15px 25px;
            border: 1px solid #e2e8f0;
            margin-bottom: 20px;
        }
        .search-input-group {
            position: relative;
            max-width: 400px;
        }
        .search-input-group i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
        .search-input-group input {
            padding-left: 45px;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
        }

        .table-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        
        .reason-col { max-width: 250px; font-style: italic; color: #64748b; font-size: 0.9rem; }
        .status-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; text-transform: uppercase; font-weight: 600; }
    </style>
</head>
<body>

<div class="header-top d-flex justify-content-between align-items-center">
    <div class="brand">
        <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-plus me-2"></i>Smart Health Care</h4>
    </div>
    <div class="user-profile d-flex align-items-center">
        <div class="text-end me-3">
            <span class="d-block fw-bold"><?= htmlspecialchars($_SESSION['username']); ?></span>
            <small class="text-success"><i class="bi bi-circle-fill" style="font-size: 8px;"></i> Administrator</small>
        </div>
        <a href="../actions/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
</div>

<nav class="sub-nav mb-4">
    <div class="container-fluid">
        <ul class="nav">
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Overview</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_doctors.php"><i class="bi bi-person-badge-fill me-2"></i>Manage Doctors</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_patients.php"><i class="bi bi-people-fill me-2"></i>Manage Patients</a></li>
            <li class="nav-item"><a class="nav-link active" href="view_appointments.php"><i class="bi bi-calendar-check-fill me-2"></i>Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-2"></i>Billing</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">Appointment Records</h3>
        <span class="badge bg-primary px-3 py-2">Total Records: <?= is_array($appointments) ? count($appointments) : 0 ?></span>
    </div>

    <div class="search-area shadow-sm">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="search-input-group">
                    <i class="bi bi-search"></i>
                    <input type="text" id="appSearch" class="form-control" placeholder="Search by patient, doctor, or specialty...">
                </div>
            </div>
            <div class="col-md-6 text-end text-muted small">
                <i class="bi bi-info-circle me-1"></i> Admin view: Read-only access to schedule monitoring.
            </div>
        </div>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="appTable">
                <thead class="table-light">
                    <tr>
                        <th>Date & Time</th>
                        <th>Patient Details</th>
                        <th>Doctor Details</th>
                        <th>Reason / Medications</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($appointments)): ?>
                        <tr><td colspan="5" class="text-center py-5">
                            <i class="bi bi-calendar-x display-4 d-block mb-3 text-muted"></i>
                            No appointment history found.
                        </td></tr>
                    <?php else: ?>
                        <?php foreach ($appointments as $app): ?>
                        <tr>
                            <td>
                                <div class="fw-bold text-primary"><?= date('d M, Y', strtotime($app['appointment_date'])) ?></div>
                                <div class="text-muted small"><i class="bi bi-clock me-1"></i><?= date('h:i A', strtotime($app['appointment_date'])) ?></div>
                            </td>
                            <td>
                                <div class="fw-bold name-cell"><?= htmlspecialchars($app['patient_name']) ?></div>
                                <div class="small text-muted">ID: #PAT-<?= $app['patient_id'] ?></div>
                            </td>
                            <td>
                                <div class="fw-bold doc-cell">Dr. <?= htmlspecialchars($app['doctor_name']) ?></div>
                                <span class="badge bg-light text-dark border fw-normal"><?= htmlspecialchars($app['expertise']) ?></span>
                            </td>
                            <td class="reason-col">
                                <?= htmlspecialchars($app['reason'] ?: 'Routine check-up') ?>
                            </td>
                            <td>
                                <?php 
                                    $bg = 'bg-secondary';
                                    if($app['status'] == 'Scheduled' || $app['status'] == 'Confirmed') $bg = 'bg-success';
                                    if($app['status'] == 'Pending') $bg = 'bg-warning text-dark';
                                    if($app['status'] == 'Cancelled') $bg = 'bg-danger';
                                ?>
                                <span class="status-badge <?= $bg ?> text-white"><?= $app['status'] ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<footer class="mt-5 mb-3 text-center text-muted">
    <small>&copy; <?= date('Y') ?> Smart Health Care | Administration Monitoring System</small>
</footer>

<script>
document.getElementById('appSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#appTable tbody tr');
    rows.forEach(row => {
        let text = row.innerText.toLowerCase();
        row.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>