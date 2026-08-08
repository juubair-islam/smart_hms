<?php
session_start();
require 'config/db.php'; 

$doctors = [];
$error_message = null;
$active_blood_requests = 0;
$active_medicine_requests = 0;

try {
    // 1. Fetching doctors for the "Meet our Specialists" section
    $stmt_doctors = $pdo->query("SELECT d.doctor_id, d.full_name, d.expertise, d.image_url FROM doctors d");
    $doctors = $stmt_doctors->fetchAll();

    // 2. Fetching LIVE emergency counts from the database
    // (We check if the status is 'Active' so resolved ones disappear from the counter)
    $stmt_blood = $pdo->query("SELECT COUNT(*) FROM emergency_requests WHERE request_type = 'Blood' AND status = 'Active'");
    $active_blood_requests = $stmt_blood->fetchColumn();

    $stmt_meds = $pdo->query("SELECT COUNT(*) FROM emergency_requests WHERE request_type = 'Medicine' AND status = 'Active'");
    $active_medicine_requests = $stmt_meds->fetchColumn();

} catch (PDOException $e) {
    // If the tables haven't been created yet, this prevents the page from crashing and just leaves the counts at 0
    $error_message = "Database Notice: Emergency tables or doctors could not be loaded.";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart HMS | Intelligent Healthcare</title>
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
        body { font-family: 'Segoe UI', sans-serif; background-color: var(--bg); color: var(--dark); }
        
        /* Navbar */
        .navbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 0; }
        .navbar-brand { font-weight: 800; color: var(--primary) !important; letter-spacing: -0.5px; }
        
        /* Hero Section */
        .hero-section { padding: 80px 0; background: white; border-bottom: 1px solid #f1f5f9; }
        .hero-title { font-weight: 800; font-size: 3rem; color: var(--dark); line-height: 1.2; }
        
        /* Floating Live Widget */
        .live-widget {
            background: white;
            border-radius: 15px;
            box-shadow: 0 15px 30px rgba(0,0,0,0.08);
            border: 1px solid #f1f5f9;
            transform: translateY(-50%);
            z-index: 10;
            position: relative;
        }
        .pulse-dot {
            width: 12px; height: 12px; background-color: var(--danger);
            border-radius: 50%; display: inline-block;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0.7); }
            70% { box-shadow: 0 0 0 10px rgba(230, 57, 70, 0); }
            100% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0); }
        }

        /* Doctor Cards */
        .doctor-card {
            border: none;
            border-radius: 20px;
            background: white;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            overflow: hidden;
        }
        .doctor-card:hover { transform: translateY(-10px); box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1); }
        .doctor-img { height: 260px; object-fit: cover; width: 100%; background: var(--accent); }
        
        /* Features */
        .feature-box {
            padding: 30px;
            background: white;
            border-radius: 20px;
            height: 100%;
            border: 1px solid #f1f5f9;
            transition: 0.3s;
        }
        .feature-box:hover { border-color: var(--primary); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        
        /* Specialized Red Border for Emergency Features */
        .feature-box.emergency:hover { border-color: var(--danger); }
        
        .feature-icon {
            width: 60px; height: 60px; background: #eff6ff;
            color: var(--primary); border-radius: 15px;
            display: flex; align-items: center; justify-content: center;
            font-size: 1.5rem; margin-bottom: 20px;
        }
        .feature-icon.emergency-icon { background: #ffe5e5; color: var(--danger); }

        .btn-primary { background: var(--primary); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; }
        .btn-outline-primary { border-radius: 12px; padding: 12px 25px; font-weight: 600; border-color: var(--primary); color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); color: Red; }
        
        .btn-danger { background: var(--danger); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; }
        .btn-outline-danger { border-radius: 12px; padding: 12px 25px; font-weight: 600; }
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

<section class="hero-section pb-0">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <span class="badge bg-danger bg-opacity-10 text-danger px-3 py-2 rounded-pill mb-3 border border-danger">EMERGENCY NETWORK</span>
                <h1 class="hero-title mb-4">Smart Care for a <span class="text-primary">Healthier</span> Tomorrow.</h1>
                <p class="lead text-muted mb-5">Experience AI-driven diagnostics, seamless appointment booking, and our new live blood & medicine tracking ecosystem.</p>
                <div class="d-flex flex-wrap gap-3">
                    <a href="donor_register.php" class="btn btn-danger btn-lg px-4"><i class="bi bi-droplet-fill me-2"></i>Become a Donor</a>
                    <a href="#doctors" class="btn btn-outline-primary btn-lg px-4 bg-white">Find a Doctor</a>
                </div>
            </div>
            <div class="col-lg-7">
                <div class="shadow-lg rounded-4 overflow-hidden position-relative" style="height: 400px;">
                    <img src="https://images.unsplash.com/photo-1519494026892-80bbd2d6fd0d?auto=format&fit=crop&w=1200&q=80" class="w-100 h-100 object-fit-cover" alt="Hospital">
                </div>
                
                <div class="col-10 col-md-8 mx-auto live-widget p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="pulse-dot"></span>
                            <span class="fw-bold text-dark mb-0">Active Emergencies</span>
                        </div>
                        <span class="badge bg-light text-muted border">Monitored 24/7</span>
                    </div>
                    <hr class="my-2 text-muted">
                    <div class="row text-center g-0">
                        <div class="col-6 border-end">
                            <h4 class="text-danger fw-bold mb-0"><?= htmlspecialchars($active_blood_requests) ?></h4>
                            <small class="text-muted">Blood Requests</small>
                        </div>
                        <div class="col-6">
                            <h4 class="text-primary fw-bold mb-0"><?= htmlspecialchars($active_medicine_requests) ?></h4>
                            <small class="text-muted">Rare Medicines</small>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</section>

<section class="py-5 mt-4 bg-light" id="emergency-features">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h2 class="fw-bold fs-2"><span class="text-danger">Rapid Response</span> Ecosystem</h2>
            <p class="text-muted">Connecting hospitals, donors, and critical supplies in real-time.</p>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box emergency">
                    <div class="feature-icon emergency-icon"><i class="bi bi-geo-alt-fill"></i></div>
                    <h5 class="fw-bold">Live Request Map</h5>
                    <p class="text-muted small">Hospitals instantly broadcast emergency blood or medicine needs, displayed dynamically on our interactive map.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box emergency">
                    <div class="feature-icon emergency-icon"><i class="bi bi-person-check-fill"></i></div>
                    <h5 class="fw-bold">Donor Check-in System</h5>
                    <p class="text-muted small">Registered donors can view nearby requests, confirm their availability, and check-in directly through the portal.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box emergency">
                    <div class="feature-icon emergency-icon"><i class="bi bi-capsule"></i></div>
                    <h5 class="fw-bold">Rare Medicine Inventory</h5>
                    <p class="text-muted small">Hospitals update and share their inventory of rare medicines, allowing rapid transfers during critical shortages.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5" id="features">
    <div class="container py-4">
        <div class="text-center mb-5">
            <span class="badge bg-primary-subtle text-primary px-3 py-2 rounded-pill mb-3">CORE SYSTEM</span>
            <h2 class="fw-bold fs-1">Intelligent Healthcare</h2>
        </div>
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-robot"></i></div>
                    <h4 class="fw-bold">AI Disease Prediction</h4>
                    <p class="text-muted">Advanced ML models analyze symptoms to provide instant health risk assessments and confidence scores.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-shield-check"></i></div>
                    <h4 class="fw-bold">Secure Health Records</h4>
                    <p class="text-muted">Your medical history, encrypted and centralized. Accessible only to you and your assigned doctors.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="feature-icon"><i class="bi bi-bell"></i></div>
                    <h4 class="fw-bold">Preventive Alerts</h4>
                    <p class="text-muted">Smart reminders for annual checkups and screenings based on your age and health profile.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light" id="doctors">
    <div class="container py-5">
        <h2 class="fw-bold mb-5"><i class="bi bi-star-fill text-warning me-2"></i>Our Specialists</h2>
        
        <?php if ($error_message): ?>
            <div class="alert alert-warning"><?= htmlspecialchars($error_message) ?></div>
        <?php endif; ?>

        <div class="row g-4">
            <?php foreach ($doctors as $doctor): ?>
                <div class="col-md-3">
                    <div class="doctor-card">
                        <img src="<?= htmlspecialchars($doctor['image_url'] ?: 'https://via.placeholder.com/400') ?>" class="doctor-img" alt="Doctor">
                        <div class="p-4 text-center">
                            <h5 class="fw-bold mb-1"><?= htmlspecialchars($doctor['full_name']) ?></h5>
                            <p class="text-primary small fw-bold mb-3"><?= htmlspecialchars($doctor['expertise']) ?></p>
                            <a href="patient_access.php" class="btn btn-outline-primary btn-sm w-100 rounded-pill">Book Appointment</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<footer class="py-5 bg-dark text-white-50 text-center">
    <div class="container">
        <p class="mb-0 small">&copy; <?= date('Y'); ?> Smart Healthcare Management System. Built for excellence.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>