<?php
session_start();
// If already logged in, redirect them to their respective dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'Admin') header('Location: admin/admin_dashboard.php');
    if ($_SESSION['role'] === 'Doctor') header('Location: doctor/dashboard.php');
    exit;
}
require 'config/db.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Portal | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #0077b6;
            --accent: #90e0ef;
            --danger: #e63946;
            --dark: #1e293b;
            --bg: #f8fafc;
        }
        body { 
            font-family: 'Segoe UI', sans-serif; 
            background-color: var(--bg); 
            color: var(--dark); 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
        }
        
        /* Exact Navbar from Index */
        .navbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 0; }
        .navbar-brand { font-weight: 800; color: var(--primary) !important; letter-spacing: -0.5px; }
        
        .pulse-dot {
            width: 8px; height: 8px; background-color: var(--danger);
            border-radius: 50%; display: inline-block;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(230, 57, 70, 0); }
            100% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0); }
        }

        .btn-primary { background: var(--primary); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; }
        .btn-outline-primary { border-radius: 12px; padding: 12px 25px; font-weight: 600; border-color: var(--primary); color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); color: white; }
        .btn-outline-danger { border-radius: 12px; padding: 12px 25px; font-weight: 600; }

        /* Centered Content Wrapper */
        .content-wrapper { flex: 1 0 auto; display: flex; align-items: center; justify-content: center; padding: 50px 0; }

        /* Clean Staff Card */
        .staff-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 40px;
            width: 100%;
            max-width: 450px;
        }

        .staff-icon-box {
            width: 70px;
            height: 70px;
            background: #eff6ff;
            color: var(--primary);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
        }

        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.25rem rgba(0, 119, 182, 0.25);
            background-color: white;
        }

        .input-group-text {
            background: white;
            border-right: none;
            color: #64748b;
            border-radius: 10px 0 0 10px;
        }

        .input-group .form-control, .input-group .form-select {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-login {
            background-color: var(--dark);
            color: white;
            border-radius: 10px;
            padding: 14px;
            font-weight: 700;
            border: none;
            width: 100%;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-login:hover {
            background-color: var(--primary);
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(0, 119, 182, 0.2);
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fs-3" href="index.php">
            <i class="bi bi-heart-pulse-fill me-2"></i>Smart HMS
        </a>
        <div class="d-flex gap-2">
            <a href="live_map.php" class="btn btn-outline-danger d-flex align-items-center gap-2">
                <span class="pulse-dot" style="width: 8px; height: 8px;"></span> Live Map
            </a>
            <a href="patient_access.php" class="btn btn-outline-primary">Patient Portal</a>
            <a href="staff_access.php" class="btn btn-primary"><i class="bi bi-person-badge me-2"></i>Staff Portal</a>
        </div>
    </div>
</nav>

<div class="content-wrapper container">
    <div class="staff-card">
        <div class="text-center">
            <div class="staff-icon-box">
                <i class="bi bi-shield-lock-fill"></i>
            </div>
            <h3 class="fw-bold text-dark mb-1">Staff Portal</h3>
            <p class="text-muted small mb-4">Secure Gateway for Doctors & Administrators</p>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger border-0 small fw-bold mb-4 rounded-3 shadow-sm bg-danger bg-opacity-10 text-danger">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success border-0 small fw-bold mb-4 rounded-3 shadow-sm bg-success bg-opacity-10 text-success">
                <i class="bi bi-check-circle-fill me-2"></i> <?= $_SESSION['success']; unset($_SESSION['success']); ?>
            </div>
        <?php endif; ?>

        <form action="actions/login_user.php" method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Authorization Level</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="bi bi-layers-half"></i></span>
                    <select class="form-select fw-bold" name="role_check" required>
                        <option value="Doctor">Medical Doctor</option>
                        <option value="Admin">System Administrator</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold small text-muted text-uppercase">Staff ID / Username</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="bi bi-person-badge"></i></span>
                    <input type="text" class="form-control fw-bold" name="username" placeholder="Enter Staff ID" required>
                </div>
            </div>

            <div class="mb-4">
                <label class="form-label fw-bold small text-muted text-uppercase">Security Password</label>
                <div class="input-group shadow-sm">
                    <span class="input-group-text"><i class="bi bi-key-fill"></i></span>
                    <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                </div>
            </div>

            <button type="submit" class="btn btn-login shadow-sm mb-2">
                Verify & Authorize <i class="bi bi-arrow-right-circle ms-1"></i>
            </button>
        </form>
    </div>
</div>

<footer class="py-5 bg-dark text-white-50 text-center mt-auto">
    <div class="container">
        <p class="mb-0 small">&copy; <?= date('Y'); ?> Smart Healthcare Management System. Built for excellence.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>