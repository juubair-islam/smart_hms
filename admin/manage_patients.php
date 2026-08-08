<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

// Fetch all patients from database
try {
    // We fetch patient details and their registration date
    $stmt = $pdo->query("SELECT * FROM Patients ORDER BY created_at DESC");
    $patients = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error fetching patients: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Patients | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-blue: #0077b6;
            --light-bg: #f8f9fa;
            --nav-dark: #1e293b;
        }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', sans-serif; }
        
        /* Layer 1: Brand Header */
        .header-top {
            background: #fff;
            padding: 12px 30px;
            border-bottom: 1px solid #e2e8f0;
        }
        
        /* Layer 2: Action Navbar */
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

        /* Search Section Styling */
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

        /* Table Styling */
        .table-container {
            background: #fff;
            border-radius: 12px;
            padding: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 2px 4px rgba(0,0,0,0.02);
        }
        .patient-avatar {
            width: 40px;
            height: 40px;
            background-color: #e2e8f0;
            color: var(--primary-blue);
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            font-weight: bold;
            font-size: 1.1rem;
        }
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
            <li class="nav-item"><a class="nav-link active" href="manage_patients.php"><i class="bi bi-people-fill me-2"></i>Manage Patients</a></li>
            <li class="nav-item"><a class="nav-link" href="view_appointments.php"><i class="bi bi-calendar-check-fill me-2"></i>Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-2"></i>Billing</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">Patient Directory</h3>
        <span class="badge bg-primary px-3 py-2">Total Patients: <?= count($patients) ?></span>
    </div>

    <div class="search-area shadow-sm">
        <div class="search-input-group">
            <i class="bi bi-search"></i>
            <input type="text" id="patientSearch" class="form-control" placeholder="Search by name, contact, or ID...">
        </div>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="patientTable">
                <thead class="table-light">
                    <tr>
                        <th>Patient</th>
                        <th>Full Name</th>
                        <th>Date of Birth</th>
                        <th>Gender</th>
                        <th>Contact Number</th>
                        <th>Registered On</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($patients)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people border rounded-circle p-3 display-4 d-block mx-auto mb-3"></i>
                                No patients registered in the system.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($patients as $p): ?>
                        <tr>
                            <td>
                                <div class="patient-avatar">
                                    <?= strtoupper(substr($p['full_name'], 0, 1)) ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-bold name-cell"><?= htmlspecialchars($p['full_name']) ?></div>
                                <small class="text-muted id-cell">#PAT-<?= $p['patient_id'] ?></small>
                            </td>
                            <td><?= date('d M, Y', strtotime($p['date_of_birth'])) ?></td>
                            <td><?= htmlspecialchars($p['gender']) ?></td>
                            <td class="contact-cell"><?= htmlspecialchars($p['contact_phone']) ?></td>
                            <td><?= date('d M, Y', strtotime($p['created_at'])) ?></td>
<td class="text-center">
    <div class="btn-group">
        <a href="view_patient.php?id=<?= $p['patient_id'] ?>" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye"></i> View
        </a>
        
        <button onclick="confirmDelete(<?= $p['patient_id'] ?>)" class="btn btn-sm btn-outline-danger">
            <i class="bi bi-trash"></i>
        </button>
    </div>
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
    <small>&copy; <?= date('Y') ?> Smart Health Care Management System | Admin Portal</small>
</footer>

<script>
// --- REAL-TIME SEARCH LOGIC ---
document.getElementById('patientSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#patientTable tbody tr');

    rows.forEach(row => {
        let name = row.querySelector('.name-cell').textContent.toLowerCase();
        let contact = row.querySelector('.contact-cell').textContent.toLowerCase();
        let id = row.querySelector('.id-cell').textContent.toLowerCase();
        
        if (name.includes(filter) || contact.includes(filter) || id.includes(filter)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }
    });
});

function confirmDelete(id) {
    if (confirm("Are you sure you want to delete this patient? This will remove all their medical records and appointments!")) {
        window.location.href = "../actions/delete_patient.php?id=" + id;
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>