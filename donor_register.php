<?php
session_start();
require 'config/db.php';

$login_error = null;

// Handle the Login form submission directly on this page
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['form_type']) && $_POST['form_type'] == 'login') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.user_id, u.password_hash, u.role, p.is_donor FROM users u JOIN patients p ON u.user_id = p.patient_id WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_donor'] == 1) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = 'Donor';
            header("Location: donor_dashboard.php");
            exit();
        } else {
            $login_error = "This account is not registered as a Donor. Please use the Patient Portal.";
        }
    } else {
        $login_error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Portal | Smart HMS</title>
    
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
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; }
        
        /* EXACT MATCH NAVBAR */
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
        .btn-danger { background: var(--danger); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; }
        .btn-outline-danger { border-radius: 12px; padding: 12px 25px; font-weight: 600; }

        /* Form UI Styling */
        .split-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        .left-panel {
            background: linear-gradient(135deg, #e63946 0%, #a8202d 100%);
            color: white;
            padding: 50px 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .right-panel { padding: 50px 40px; }
        
        .form-control, .form-select {
            border-radius: 10px;
            padding: 12px 15px;
            border: 1px solid #cbd5e1;
            background-color: #f8fafc;
        }
        .form-control:focus, .form-select:focus {
            border-color: var(--danger);
            box-shadow: 0 0 0 0.25rem rgba(230, 57, 70, 0.25);
            background-color: white;
        }
        
        .feature-bullet {
            display: flex;
            align-items: flex-start;
            margin-bottom: 25px;
        }
        .feature-bullet i {
            font-size: 1.5rem;
            margin-right: 15px;
            background: rgba(255,255,255,0.2);
            width: 45px; height: 45px;
            display: flex; align-items: center; justify-content: center;
            border-radius: 12px;
        }

        /* Nav Pills for toggling Login/Register */
        .nav-pills .nav-link {
            border-radius: 10px;
            color: #64748b;
            font-weight: 600;
        }
        .nav-pills .nav-link.active {
            background-color: #fef2f2;
            color: var(--danger);
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

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-xl-10">
            <div class="split-card row g-0">
                
                <div class="col-lg-5 left-panel">
                    <h2 class="fw-bold mb-4">Become a <br>Medical Hero.</h2>
                    <p class="mb-5" style="opacity: 0.9;">Join the Smart HMS Rapid Response Network. Your registration directly connects you to hospitals facing critical shortages.</p>
                    
                    <div class="feature-bullet">
                        <i class="bi bi-geo-alt-fill"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Targeted Alerts</h6>
                            <p class="small mb-0" style="opacity: 0.8;">Only get notified when an emergency is requested in your selected district.</p>
                        </div>
                    </div>
                    <div class="feature-bullet">
                        <i class="bi bi-shield-lock-fill"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Total Privacy</h6>
                            <p class="small mb-0" style="opacity: 0.8;">Your exact address is hidden. We only route alerts based on your general area.</p>
                        </div>
                    </div>
                    <div class="feature-bullet">
                        <i class="bi bi-activity"></i>
                        <div>
                            <h6 class="fw-bold mb-1">Direct Impact</h6>
                            <p class="small mb-0" style="opacity: 0.8;">Bypass the bureaucracy. Check-in directly with doctors needing your blood group.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 right-panel">
                    
                    <ul class="nav nav-pills nav-justified mb-4 border border-secondary border-opacity-25 rounded-3 p-1" id="donorTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="login-tab" data-bs-toggle="pill" data-bs-target="#login" type="button" role="tab">
                                <i class="bi bi-box-arrow-in-right me-2"></i>Login
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="register-tab" data-bs-toggle="pill" data-bs-target="#register" type="button" role="tab">
                                <i class="bi bi-person-plus-fill me-2"></i>Register
                            </button>
                        </li>
                    </ul>

                    <?php if ($login_error): ?>
                        <div class="alert alert-danger small rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $login_error ?></div>
                    <?php endif; ?>

                    <div class="tab-content" id="donorTabsContent">
                        
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <h4 class="fw-bold mb-1 text-dark">Welcome Back</h4>
                            <p class="text-muted mb-4 small">Enter your credentials to access your donor dashboard.</p>
                            
                            <form method="POST" action="">
                                <input type="hidden" name="form_type" value="login">
                                
                                <div class="mb-3">
                                    <label class="form-label fw-bold small text-muted">Username</label>
                                    <input type="text" name="username" class="form-control form-control-lg" required>
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-bold small text-muted">Password</label>
                                    <input type="password" name="password" class="form-control form-control-lg" required>
                                </div>
                                <button type="submit" class="btn btn-danger w-100 py-3 fw-bold fs-5 shadow-sm">
                                    Secure Login <i class="bi bi-shield-lock-fill ms-2"></i>
                                </button>
                            </form>
                        </div>

                        <div class="tab-pane fade" id="register" role="tabpanel">
                            <h4 class="fw-bold mb-1 text-dark">Create Account</h4>
                            <p class="text-muted mb-4 small">Complete your profile to activate your donor status.</p>

                            <form action="actions/process_donor.php" method="POST">
                                <input type="hidden" name="form_type" value="register">
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Username</label>
                                        <input type="text" name="username" class="form-control" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold small text-muted">Password</label>
                                        <input type="password" name="password" class="form-control" required>
                                    </div>
                                    
                                    <div class="col-12 mt-3">
                                        <label class="form-label fw-bold small text-muted">Full Legal Name</label>
                                        <input type="text" name="full_name" class="form-control" required>
                                    </div>
                                    <div class="col-md-6 mt-3">
                                        <label class="form-label fw-bold small text-muted">Blood Group</label>
                                        <select name="blood_group" class="form-select" required>
                                            <option value="" disabled selected>Select...</option>
                                            <option value="A+">A+</option><option value="A-">A-</option>
                                            <option value="B+">B+</option><option value="B-">B-</option>
                                            <option value="O+">O+</option><option value="O-">O-</option>
                                            <option value="AB+">AB+</option><option value="AB-">AB-</option>
                                        </select>
                                    </div>

                                    <div class="col-md-6 mt-3">
    <label class="form-label fw-bold small text-danger"><i class="bi bi-telephone-fill me-1"></i> Mobile Number (BD Only)</label>
    <div class="input-group shadow-sm">
        <span class="input-group-text bg-danger text-white fw-bold border-danger">+880</span>
        <input type="tel" name="contact_phone" class="form-control border-danger" pattern="1[3-9][0-9]{8}" placeholder="1XXXXXXXX" maxlength="10" required>
    </div>
    <div class="form-text small text-muted">Enter exactly 10 digits without the leading zero.</div>
</div>

                                    <div class="col-12 mt-4 bg-light p-4 rounded-3 border border-secondary border-opacity-10">
                                        <label class="form-label fw-bold small text-danger mb-3"><i class="bi bi-geo-alt-fill me-1"></i> Live Map Pairing Area</label>
                                        
                                        <button type="button" id="geoBtn" class="btn btn-outline-danger w-100 mb-3 fw-bold rounded-3 py-2" onclick="getLocationWithGeocoding()">
                                            <i class="bi bi-crosshair me-2"></i> Auto-Detect My Area via GPS
                                        </button>
                                        
                                        <div class="text-center mb-3 text-muted small fw-bold">OR CHOOSE MANUALLY</div>

                                        <select id="districtSelect" class="form-select border-danger" required onchange="setCoordinatesManually()">
                                            <option value="" disabled selected>Select your District / Area...</option>
                                            <option value="Dhaka" data-lat="23.8103" data-lng="90.4125">Dhaka</option>
                                            <option value="Chittagong" data-lat="22.3569" data-lng="91.7832">Chittagong</option>
                                            <option value="Sylhet" data-lat="24.8949" data-lng="91.8687">Sylhet</option>
                                            <option value="Rajshahi" data-lat="24.3745" data-lng="88.6042">Rajshahi</option>
                                            <option value="Khulna" data-lat="22.8456" data-lng="89.5403">Khulna</option>
                                            <option value="Barishal" data-lat="22.7010" data-lng="90.3535">Barishal</option>
                                            <option value="Rangpur" data-lat="25.7439" data-lng="89.2752">Rangpur</option>
                                            <option value="Mymensingh" data-lat="24.7471" data-lng="90.4203">Mymensingh</option>
                                            
                                            <option id="gpsOption" value="GPS" class="d-none bg-success text-white fw-bold" data-lat="" data-lng="">📍 Detected Area</option>
                                        </select>
                                        
                                        <input type="hidden" name="latitude" id="hiddenLat" required>
                                        <input type="hidden" name="longitude" id="hiddenLng" required>
                                    </div>

                                    <div class="col-12 mt-4">
                                        <button type="submit" class="btn btn-danger w-100 py-3 fw-bold fs-5 shadow-sm">
                                            Activate Profile <i class="bi bi-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Logic for Manual Selection
function setCoordinatesManually() {
    var select = document.getElementById('districtSelect');
    var selectedOption = select.options[select.selectedIndex];
    
    document.getElementById('hiddenLat').value = selectedOption.getAttribute('data-lat');
    document.getElementById('hiddenLng').value = selectedOption.getAttribute('data-lng');
}

// Logic for GPS + Reverse Geocoding
function getLocationWithGeocoding() {
    const btn = document.getElementById('geoBtn');
    
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span> Locating...';
    btn.disabled = true;

    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
            async function(position) {
                const lat = position.coords.latitude;
                const lng = position.coords.longitude;
                
                try {
                    const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=10`);
                    const data = await response.json();
                    
                    const areaName = data.address.city || data.address.state_district || data.address.state || "Detected Location";

                    document.getElementById('hiddenLat').value = lat;
                    document.getElementById('hiddenLng').value = lng;

                    const select = document.getElementById('districtSelect');
                    const gpsOpt = document.getElementById('gpsOption');
                    
                    gpsOpt.value = areaName;
                    gpsOpt.text = `📍 ${areaName} (GPS Verified)`;
                    gpsOpt.setAttribute('data-lat', lat);
                    gpsOpt.setAttribute('data-lng', lng);
                    gpsOpt.classList.remove('d-none');
                    select.value = areaName;

                    btn.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Verified: ${areaName}`;
                    btn.classList.replace('btn-outline-danger', 'btn-success');
                    btn.classList.replace('text-danger', 'text-white');

                } catch(error) {
                    alert("We found your coordinates, but couldn't find the street name. Coordinates saved anyway.");
                    document.getElementById('hiddenLat').value = lat;
                    document.getElementById('hiddenLng').value = lng;
                    btn.innerHTML = '<i class="bi bi-check-circle-fill me-2"></i> Coordinates Secured';
                    btn.classList.replace('btn-outline-danger', 'btn-success');
                    btn.classList.replace('text-danger', 'text-white');
                }
            }, 
            function(error) {
                btn.innerHTML = '<i class="bi bi-crosshair me-2"></i> Auto-Detect My Area via GPS';
                btn.disabled = false;
                alert("Location access denied. Please choose your district manually from the list.");
            }
        );
    } else {
        alert("Geolocation is not supported by this browser.");
        btn.innerHTML = '<i class="bi bi-crosshair me-2"></i> Auto-Detect My Area via GPS';
        btn.disabled = false;
    }
}
</script>
</body>
</html>