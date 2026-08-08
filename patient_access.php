<?php
session_start();
// Include the database connection for fetching flash messages if any
require 'config/db.php'; 

// Function to calculate age from DOB (used for display, not database storage)
function calculateAge($dob) {
    if (empty($dob)) return 0;
    $birthDate = new DateTime($dob);
    $currentDate = new DateTime();
    $age = $currentDate->diff($birthDate)->y;
    return $age;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Access - Smart HMS</title>
    
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
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; display: flex; flex-direction: column; min-height: 100vh; }
        
        /* Exact Match Navbar Styles */
        .navbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 0; }
        .navbar-brand { font-weight: 800; color: var(--primary) !important; letter-spacing: -0.5px; }
        
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

        .btn-primary { background: var(--primary); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; }
        .btn-outline-primary { border-radius: 12px; padding: 12px 25px; font-weight: 600; border-color: var(--primary); color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); color: white; }
        .btn-outline-danger { border-radius: 12px; padding: 12px 25px; font-weight: 600; }

        /* Main Content Wrapper to push footer down */
        .content-wrapper { flex: 1 0 auto; }

        /* Form UI Styling */
        .access-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            padding: 40px;
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
        
        /* Custom Nav Pills */
        .nav-pills .nav-link {
            border-radius: 10px;
            color: #64748b;
            font-weight: 600;
            padding: 12px;
        }
        .nav-pills .nav-link.active {
            background-color: var(--primary);
            color: white;
            box-shadow: 0 4px 10px rgba(0, 119, 182, 0.3);
        }

        /* Section Headers inside the form */
        .section-title {
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
            padding-bottom: 5px;
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
            <a href="patient_access.php" class="btn btn-primary">Patient Portal</a>
            <a href="staff_access.php" class="btn btn-outline-primary text-dark border-secondary border-opacity-25"><i class="bi bi-person-badge me-2"></i>Staff Portal</a>
        </div>
    </div>
</nav>

<div class="content-wrapper container py-5">
    <div class="text-center mb-4">
        <h2 class="fw-bold text-dark"><i class="bi bi-person-circle text-primary me-2"></i>Patient Access Portal</h2>
        <p class="text-muted">Login to view your medical records, book appointments, and manage bills.</p>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="alert alert-success alert-dismissible fade show shadow-sm rounded-3 fw-bold" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php elseif (isset($_SESSION['error'])): ?>
        <div class="row justify-content-center">
            <div class="col-lg-8 col-xl-7">
                <div class="alert alert-danger alert-dismissible fade show shadow-sm rounded-3 fw-bold" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
            <div class="access-card">
                
                <ul class="nav nav-pills nav-justified mb-4 bg-light rounded-3 p-1 border border-secondary border-opacity-10" id="patientTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Secure Login
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="register-tab" data-bs-toggle="pill" data-bs-target="#register" type="button" role="tab">
                            <i class="bi bi-person-plus-fill me-2"></i> New Registration
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="patientTabContent">
                    
                    <div class="tab-pane fade show active" id="login" role="tabpanel">
                        <form action="actions/login_user.php" method="POST">
                            <input type="hidden" name="role_check" value="Patient">
                            
                            <div class="mb-4">
                                <label for="login_contact" class="form-label fw-bold small text-muted">Registered Contact Number</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-telephone-fill"></i></span>
                                    <input type="tel" class="form-control form-control-lg border-start-0 ps-0" id="login_contact" name="username" placeholder="Enter your contact number" required>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label for="login_password" class="form-label fw-bold small text-muted">Password</label>
                                <div class="input-group shadow-sm">
                                    <span class="input-group-text bg-white text-muted border-end-0"><i class="bi bi-lock-fill"></i></span>
                                    <input type="password" class="form-control form-control-lg border-start-0 ps-0" id="login_password" name="password" placeholder="Enter your password" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 btn-lg shadow-sm fw-bold rounded-3">Access Dashboard <i class="bi bi-arrow-right ms-1"></i></button>
                        </form>
                    </div>

                    <div class="tab-pane fade" id="register" role="tabpanel">
                        <form action="actions/register_user.php" method="POST">
                            <input type="hidden" name="role" value="Patient">
                            
                            <div class="section-title">Personal Details</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-muted">Full Legal Name</label>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small text-muted">Date of Birth</label>
                                    <input type="date" class="form-control" id="reg_dob" name="date_of_birth" required onchange="calculatePatientAge()">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-bold small text-muted">Age</label>
                                    <input type="text" class="form-control bg-light text-center fw-bold text-primary" id="reg_age" readonly disabled placeholder="--">
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label fw-bold small text-muted">Gender</label>
                                    <select class="form-select" name="gender" required>
                                        <option value="" selected disabled>Select...</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-muted">Blood Group</label>
                                    <select class="form-select" name="blood_group" required>
                                        <option value="" selected disabled>Select Blood Group...</option>
                                        <option value="A+">A+</option><option value="A-">A-</option>
                                        <option value="B+">B+</option><option value="B-">B-</option>
                                        <option value="O+">O+</option><option value="O-">O-</option>
                                        <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="section-title">Account Security & Contact</div>
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Contact Number (Login ID)</label>
                                    <input type="tel" class="form-control" name="contact_phone" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-muted">Email Address (Optional)</label>
                                    <input type="email" class="form-control" name="email">
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-muted">Password</label>
                                    <input type="password" class="form-control" name="password" placeholder="Create a secure password" required>
                                </div>
                            </div>
                            
                            <div class="section-title text-danger border-danger"><i class="bi bi-shield-plus me-1"></i> Emergency Contact</div>
                            <div class="row g-3 mb-4 bg-danger bg-opacity-10 p-3 rounded-3 border border-danger border-opacity-25 mx-0">
                                <div class="col-md-12">
                                    <label class="form-label fw-bold small text-danger">Contact Name</label>
                                    <input type="text" class="form-control border-danger" name="ec_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-danger">Phone Number</label>
                                    <input type="tel" class="form-control border-danger" name="ec_number" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small text-danger">Relation to Patient</label>
                                    <input type="text" class="form-control border-danger" name="ec_relation" placeholder="e.g. Brother, Spouse" required>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary w-100 btn-lg shadow-sm fw-bold rounded-3">Register & Complete Profile</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<footer class="py-5 bg-dark text-white-50 text-center">
    <div class="container">
        <p class="mb-0 small">&copy; <?= date('Y'); ?> Smart Healthcare Management System. Built for excellence.</p>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // JavaScript to calculate Age from Date of Birth
    function calculatePatientAge() {
        const dobInput = document.getElementById('reg_dob').value;
        const ageInput = document.getElementById('reg_age');
        
        if (dobInput) {
            const birthDate = new Date(dobInput);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            ageInput.value = age + " Years";
        } else {
            ageInput.value = '';
        }
    }
</script>

</body>
</html>