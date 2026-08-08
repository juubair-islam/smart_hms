<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

try {
    $stmt = $pdo->query("SELECT * FROM Doctors ORDER BY doctor_id DESC");
    $doctors = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Error fetching doctors: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Doctors | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-blue: #0077b6;
            --light-bg: #f8f9fa;
            --nav-dark: #1e293b;
        }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', sans-serif; }
        .header-top { background: #fff; padding: 12px 30px; border-bottom: 1px solid #e2e8f0; }
        .sub-nav { background: var(--nav-dark); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .sub-nav .nav-link { color: #94a3b8; padding: 15px 20px; font-weight: 500; transition: all 0.3s; }
        .sub-nav .nav-link:hover, .sub-nav .nav-link.active { color: #fff; background: rgba(255,255,255,0.05); border-bottom: 3px solid var(--primary-blue); }
        .search-area { background: #fff; border-radius: 12px; padding: 15px 25px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .search-input-group { position: relative; max-width: 400px; }
        .search-input-group i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
        .search-input-group input { padding-left: 45px; border-radius: 8px; border: 1px solid #e2e8f0; }
        .table-container { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
        .doctor-img-table { width: 45px; height: 45px; object-fit: cover; border-radius: 50%; }
        .badge-specialty { background-color: rgba(0, 119, 182, 0.1); color: var(--primary-blue); font-weight: 600; padding: 6px 12px; border-radius: 6px; }
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
            <li class="nav-item"><a class="nav-link active" href="manage_doctors.php"><i class="bi bi-person-badge-fill me-2"></i>Manage Doctors</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_patients.php"><i class="bi bi-people-fill me-2"></i>Manage Patients</a></li>
            <li class="nav-item"><a class="nav-link" href="view_appointments.php"><i class="bi bi-calendar-check-fill me-2"></i>Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-2"></i>Billing</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid px-4">
    
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h3 class="fw-bold text-dark">Medical Staff Directory</h3>
        <span class="badge bg-primary px-3 py-2">Total Doctors: <?= count($doctors) ?></span>
    </div>

    <div class="search-area shadow-sm d-flex justify-content-between align-items-center flex-wrap">
        <div class="search-input-group mb-2 mb-md-0">
            <i class="bi bi-search"></i>
            <input type="text" id="doctorSearch" class="form-control" placeholder="Search by name or expertise...">
        </div>
        <a href="add_doctor.php" class="btn btn-primary shadow-sm">
            <i class="bi bi-person-plus-fill me-2"></i>Register New Doctor
        </a>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="doctorTable">
                <thead class="table-light">
                    <tr>
                        <th>Profile</th>
                        <th>Doctor Info</th>
                        <th>Expertise</th>
                        <th>Contact</th>
                        <th>Qualification</th>
                        <th class="text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($doctors)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-person-vcard display-4 d-block mb-3"></i>
                                No doctors registered in the system.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($doctors as $doc): ?>
                        <tr>
                            <td>
                                <img src="../<?= htmlspecialchars($doc['image_url'] ?: 'assets/img/default_doc.jpg') ?>" class="doctor-img-table border shadow-sm">
                            </td>
                            <td>
                                <div class="fw-bold name-cell"><?= htmlspecialchars($doc['full_name']) ?></div>
                                <small class="text-muted">#DOC-<?= $doc['doctor_id'] ?></small>
                            </td>
                            <td><span class="badge badge-specialty expertise-cell"><?= htmlspecialchars($doc['expertise']) ?></span></td>
                            <td><i class="bi bi-telephone me-2 text-muted"></i><?= htmlspecialchars($doc['contact_number']) ?></td>
                            <td><?= htmlspecialchars($doc['qualification']) ?></td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="edit_doctor.php?id=<?= $doc['doctor_id'] ?>" class="btn btn-sm btn-outline-primary" title="Edit Profile">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <button onclick="confirmDelete(<?= $doc['doctor_id'] ?>)" class="btn btn-sm btn-outline-danger" title="Remove Doctor">
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
document.getElementById('doctorSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#doctorTable tbody tr');
    rows.forEach(row => {
        let name = row.querySelector('.name-cell').textContent.toLowerCase();
        let expertise = row.querySelector('.expertise-cell').textContent.toLowerCase();
        row.style.display = (name.includes(filter) || expertise.includes(filter)) ? "" : "none";
    });
});

function confirmDelete(id) {
    if (confirm("CRITICAL ACTION: Deleting this doctor will also delete their login account and may affect their appointment history. Do you want to proceed?")) {
        window.location.href = "../actions/delete_doctor.php?id=" + id;
    }
}
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>