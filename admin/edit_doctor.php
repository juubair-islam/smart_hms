<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

// Fetch current doctor details
if (isset($_GET['id'])) {
    $doctor_id = $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM Doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    if (!$doctor) {
        die("Doctor not found.");
    }
} else {
    header('Location: manage_doctors.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Doctor | Smart HMS</title>
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

        .form-card {
            background: #fff;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            max-width: 800px;
            margin: 20px auto;
            overflow: hidden;
        }
        .form-header { background: #f1f5f9; padding: 20px; border-bottom: 1px solid #e2e8f0; }
        .preview-img { width: 120px; height: 120px; object-fit: cover; border-radius: 10px; border: 2px solid var(--primary-blue); margin-bottom: 10px; }
        /* Style for readonly field to look distinct */
        .form-control[readonly] { background-color: #e9ecef; cursor: not-allowed; font-weight: 600; }
    </style>
</head>
<body>

<div class="header-top d-flex justify-content-between align-items-center">
    <div class="brand"><h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-plus me-2"></i>Smart Health Care</h4></div>
    <div class="user-profile d-flex align-items-center">
        <span class="me-3 fw-bold"><?= htmlspecialchars($_SESSION['username']); ?></span>
        <a href="../actions/logout.php" class="btn btn-outline-danger btn-sm">Logout</a>
    </div>
</div>

<nav class="sub-nav mb-4">
    <div class="container-fluid">
        <ul class="nav">
            <li class="nav-item"><a class="nav-link" href="admin_dashboard.php">Overview</a></li>
            <li class="nav-item"><a class="nav-link active" href="manage_doctors.php">Manage Doctors</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_patients.php">Manage Patients</a></li>
        </ul>
    </div>
</nav>

<div class="container">
    <div class="form-card shadow-sm">
        <div class="form-header">
            <h4 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Update Doctor Profile</h4>
            <small class="text-muted">Modify professional details for #DOC-<?= $doctor['doctor_id'] ?></small>
        </div>

        <div class="p-4">
            <form action="../actions/update_doctor.php" method="POST" enctype="multipart/form-data">
                <input type="hidden" name="doctor_id" value="<?= $doctor['doctor_id'] ?>">
                
                <div class="row g-4">
                    <div class="col-md-4 text-center border-end">
                        <label class="form-label d-block fw-bold">Current Photo</label>
                        <img id="imgPreview" src="../<?= htmlspecialchars($doctor['image_url'] ?: 'assets/img/default_doc.jpg') ?>" class="preview-img" alt="Doctor">
                        <input type="file" name="doctor_image" class="form-control form-control-sm" id="imageInput" accept="image/*">
                        <small class="text-muted d-block mt-2">Leave blank to keep current photo</small>
                    </div>

                    <div class="col-md-8">
                        <div class="row g-3">
                            <div class="col-12">
                                <label class="form-label">Full Name (Cannot be changed)</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($doctor['full_name']) ?>" readonly>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Expertise / Specialty</label>
                                <select name="expertise" class="form-select" required>
                                    <?php 
                                    $specialties = ['Cardiology', 'Neurology', 'General Medicine', 'Pediatrics', 'Dental', 'Orthopedics'];
                                    foreach($specialties as $s): ?>
                                        <option value="<?= $s ?>" <?= ($doctor['expertise'] == $s) ? 'selected' : '' ?>><?= $s ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Qualification</label>
                                <input type="text" name="qualification" class="form-control" value="<?= htmlspecialchars($doctor['qualification']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Contact Number</label>
                                <input type="text" name="contact" class="form-control" value="<?= htmlspecialchars($doctor['contact_number']) ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label">Gender</label>
                                <select name="gender" class="form-select">
                                    <option value="Male" <?= ($doctor['gender'] == 'Male') ? 'selected' : '' ?>>Male</option>
                                    <option value="Female" <?= ($doctor['gender'] == 'Female') ? 'selected' : '' ?>>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="mt-4 d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-4">Update Profile</button>
                            <a href="manage_doctors.php" class="btn btn-outline-secondary px-4">Cancel</a>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    imageInput.onchange = evt => {
        const [file] = imageInput.files;
        if (file) imgPreview.src = URL.createObjectURL(file);
    }
</script>
</body>
</html>