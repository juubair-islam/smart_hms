<?php
session_start();
require '../config/db.php';

$adminExists = 0;
try {
    // Check if an Admin already exists in the system
    $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Admin'");
    $adminExists = 0; // $stmt->fetchColumn();
} catch (PDOException $e) {
    // If table doesn't exist yet, we assume 0 admins
    $adminExists = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Admin Setup | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary: #0077b6;
            --secondary: #1e293b;
            --danger: #e63946;
            --bg: #f8fafc;
        }
        body { 
            background-color: var(--bg); 
            font-family: 'Segoe UI', sans-serif; 
            display: flex; 
            flex-direction: column; 
            min-height: 100vh; 
        }
        
        /* Unified Navbar */
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

        .content-wrapper { flex: 1 0 auto; display: flex; align-items: center; justify-content: center; padding: 50px 0; }

        /* Setup Card */
        .setup-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(30, 41, 59, 0.1);
            border: 1px solid #e2e8f0;
            padding: 40px;
            width: 100%;
            max-width: 500px;
        }

        .setup-icon-box {
            width: 70px;
            height: 70px;
            background: var(--secondary);
            color: white;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            font-size: 2rem;
            box-shadow: 0 8px 16px rgba(30, 41, 59, 0.2);
        }
        
        .locked-icon-box {
            background: #fef2f2;
            color: var(--danger);
            box-shadow: none;
            border: 1px solid #fecaca;
        }

        .form-control {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }

        .form-control:focus {
            border-color: var(--secondary);
            box-shadow: 0 0 0 0.25rem rgba(30, 41, 59, 0.1);
        }

        .btn-setup {
            background-color: var(--secondary);
            color: white;
            border-radius: 10px;
            padding: 14px;
            font-weight: 700;
            border: none;
            transition: 0.3s;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-setup:hover {
            background-color: #0f172a;
            color: white;
            transform: translateY(-2px);
        }

        .warning-box {
            background: #fffbeb;
            border: 1px solid #fde68a;
            color: #92400e;
            border-radius: 12px;
            padding: 15px;
            font-size: 0.85rem;
            margin-bottom: 25px;
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fs-3" href="../index.php">
            <i class="bi bi-heart-pulse-fill me-2"></i>Smart HMS
        </a>
        <div class="d-flex gap-2">
            <a href="../live_map.php" class="btn btn-outline-danger d-flex align-items-center gap-2 rounded-pill px-3 py-2 fw-bold">
                <span class="pulse-dot"></span> Live Radar
            </a>
            <a href="../patient_access.php" class="btn btn-outline-primary fw-bold">Patient Portal</a>
            <a href="../staff_access.php" class="btn btn-primary fw-bold"><i class="bi bi-person-badge me-2"></i>Staff Portal</a>
        </div>
    </div>
</nav>

<div class="content-wrapper container">
    <div class="setup-card">
        
        <?php if ($adminExists > 0 && !isset($_SESSION['success'])): ?>
            <div class="text-center">
                <div class="setup-icon-box locked-icon-box">
                    <i class="bi bi-shield-lock-fill"></i>
                </div>
                <h3 class="fw-bold text-dark mb-2">System Locked</h3>
                <p class="text-muted mb-4">A Master Administrator is already registered in the system. For security purposes, automated admin creation has been disabled.</p>
                
                <hr class="text-muted opacity-25 my-4">
                
                <a href="../staff_access.php" class="btn btn-setup w-100 shadow-sm py-3 fs-6">
                    Proceed to Staff Login <i class="bi bi-arrow-right-circle ms-1"></i>
                </a>
            </div>

        <?php else: ?>
            <div class="text-center">
                <div class="setup-icon-box">
                    <i class="bi bi-gear-wide-connected"></i>
                </div>
                <h3 class="fw-bold text-dark mb-1">System Initialization</h3>
                <p class="text-muted small mb-4">Create the master administrator account</p>
            </div>

            <div class="warning-box">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <strong>Security Warning:</strong> This setup page will lock automatically once the first administrator is created. Keep these credentials safe.
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger border-0 small fw-bold mb-4 rounded-3 shadow-sm bg-danger bg-opacity-10 text-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success border-0 small fw-bold mb-4 rounded-3 shadow-sm bg-success bg-opacity-10 text-success text-center">
                    <i class="bi bi-check-circle-fill d-block fs-1 mb-2"></i> 
                    <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <br><br>
                    <a href="../staff_access.php" class="btn btn-sm btn-success fw-bold px-4">Login Now</a>
                </div>
            <?php endif; ?>

            <?php if (!isset($_SESSION['success'])): ?>
            <form action="../actions/register_admin.php" method="POST">
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Full Name</label>
                    <input type="text" class="form-control fw-bold" name="name" placeholder="e.g. System Admin" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label fw-bold small text-muted text-uppercase">Admin Username</label>
                    <input type="text" class="form-control" name="username" placeholder="Choose a secure login ID" required>
                </div>
                
                <div class="mb-4">
                    <label class="form-label fw-bold small text-muted text-uppercase">Master Password</label>
                    <input type="password" class="form-control" name="password" placeholder="••••••••" required>
                </div>
                
                <button type="submit" class="btn btn-setup w-100 shadow-sm py-3 fs-6">
                    Initialize System <i class="bi bi-hdd-network ms-1"></i>
                </button>
            </form>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>

<footer class="py-4 bg-white border-top text-center mt-auto">
    <div class="container">
        <p class="mb-0 small text-muted">&copy; <?= date('Y'); ?> Smart Healthcare Management System | Secure Initialization Node</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>