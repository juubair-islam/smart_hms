<?php
session_start();
require 'config/db.php';

// Ensure only logged-in donors access this
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Donor') {
    header("Location: donor_register.php");
    exit();
}

$donor_id = $_SESSION['user_id'];

// 1. Fetch Donor Profile
$stmt_profile = $pdo->prepare("SELECT * FROM patients WHERE patient_id = ?");
$stmt_profile->execute([$donor_id]);
$donor = $stmt_profile->fetch();

// 2. Fetch Active Emergencies (General Feed)
$stmt_emergencies = $pdo->query("
    SELECT e.*, d.full_name as doctor_name 
    FROM emergency_requests e
    LEFT JOIN doctors d ON e.requester_id = d.doctor_id 
    WHERE e.status = 'Active' 
    ORDER BY e.urgency_level ASC, e.created_at DESC
");
$active_emergencies = $stmt_emergencies->fetchAll();

// 3. Fetch Donation History
$stmt_history = $pdo->prepare("
    SELECT dr.status, dr.response_time, e.request_type, e.blood_group, e.medicine_name 
    FROM donor_responses dr
    JOIN emergency_requests e ON dr.request_id = e.request_id
    WHERE dr.donor_id = ? ORDER BY dr.response_time DESC
");
$stmt_history->execute([$donor_id]);
$history = $stmt_history->fetchAll();

// 4. Fetch TARGETED ALERTS (Where someone requested this specific donor's blood type)
$stmt_alerts = $pdo->prepare("
    SELECT n.notification_id, n.message, n.created_at, e.blood_group, e.urgency_level, e.request_id 
    FROM donor_notifications n
    JOIN emergency_requests e ON n.request_id = e.request_id
    WHERE n.donor_id = ? AND n.is_read = 0 AND e.status = 'Active'
    ORDER BY n.created_at DESC
");
$stmt_alerts->execute([$donor_id]);
$targeted_alerts = $stmt_alerts->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Donor Dashboard | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #0077b6; --danger: #e63946; --bg: #f8fafc; }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif;}
        
        .navbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 0; }
        .navbar-brand { font-weight: 800; color: var(--primary) !important; letter-spacing: -0.5px; }

        .card { border: none; border-radius: 16px; box-shadow: 0 10px 20px rgba(0,0,0,0.03); border: 1px solid #e2e8f0; overflow: hidden;}
        .profile-header { background: linear-gradient(135deg, #e63946 0%, #a8202d 100%); color: white; padding: 30px 20px 50px; text-align: center; }
        .profile-avatar { width: 90px; height: 90px; background: white; color: var(--danger); font-size: 2.5rem; font-weight: 800; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: -45px auto 15px; border: 4px solid var(--bg); box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        
        .pulse-dot { width: 12px; height: 12px; background-color: var(--danger); border-radius: 50%; display: inline-block; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(230, 57, 70, 0); } 100% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0); } }

        .emergency-card { transition: 0.3s; border-left: 5px solid var(--danger); }
        .emergency-card.medicine { border-left-color: var(--primary); }
        .emergency-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.08); }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fs-3" href="index.php"><i class="bi bi-heart-pulse-fill me-2"></i>Smart HMS</a>
        <div class="d-flex align-items-center gap-3">
            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger rounded-pill px-3 py-2"><i class="bi bi-shield-check me-1"></i> Verified Donor</span>
            <a href="actions/logout.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-3">Logout</a>
        </div>
    </div>
</nav>

<div class="container py-5">
    
    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success fw-bold rounded-3 shadow-sm"><i class="bi bi-check-circle-fill me-2"></i><?= $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
    <?php endif; ?>

    <div class="row g-4">
        
        <div class="col-lg-4">
            <div class="card mb-4 bg-transparent">
                <div class="profile-header rounded-top-4">
                    <h5 class="fw-bold mb-0">Hero Profile</h5>
                </div>
                <div class="card-body bg-white rounded-bottom-4 text-center pt-0 px-4 pb-4">
                    <div class="profile-avatar"><?= htmlspecialchars($donor['blood_group'] ?: 'O+') ?></div>
                    <h4 class="fw-bold mb-1"><?= htmlspecialchars($donor['full_name']) ?></h4>
                    
                    <div class="d-flex flex-column align-items-center gap-2 mt-3 mb-4 text-muted small">
                        <div><i class="bi bi-telephone-fill text-danger me-1"></i> <?= htmlspecialchars($donor['contact_phone']) ?></div>
                        <div class="bg-light px-3 py-2 rounded-pill border">
                            <i class="bi bi-geo-alt-fill text-primary me-1"></i> <span id="displayArea" class="fw-bold text-dark">Locating via GPS...</span>
                        </div>
                    </div>

                    <button class="btn btn-outline-danger w-100 fw-bold rounded-pill" data-bs-toggle="modal" data-bs-target="#updateProfileModal">
                        <i class="bi bi-pencil-square me-2"></i>Edit Profile
                    </button>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-4 border-bottom pb-2"><i class="bi bi-clock-history me-2 text-danger"></i>Activity History</h6>
                    <?php if(empty($history)): ?>
                        <div class="text-center text-muted p-3 bg-light rounded-3">
                            <i class="bi bi-info-circle fs-3 d-block mb-2"></i>
                            <small>No check-ins yet. Respond to an alert to save a life!</small>
                        </div>
                    <?php else: ?>
                        <?php foreach($history as $h): ?>
                            <div class="d-flex justify-content-between align-items-center mb-3 p-2 border-bottom border-light">
                                <div>
                                    <h6 class="fw-bold mb-0 text-dark small">
                                        <?= $h['request_type'] == 'Blood' ? '<i class="bi bi-droplet-fill text-danger me-1"></i>'.$h['blood_group'].' Blood' : '<i class="bi bi-capsule text-primary me-1"></i>'.$h['medicine_name'] ?>
                                    </h6>
                                    <small class="text-muted" style="font-size: 0.75rem;"><?= date('d M, Y - h:i A', strtotime($h['response_time'])) ?></small>
                                </div>
                                <span class="badge bg-<?= $h['status'] == 'Completed' ? 'success' : ($h['status'] == 'Cancelled' ? 'secondary' : 'primary') ?> rounded-pill"><?= $h['status'] ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-8">
            
            <?php if(!empty($targeted_alerts)): ?>
                <div class="alert bg-danger text-white border-0 shadow-lg rounded-4 p-4 mb-4 position-relative overflow-hidden">
                    <div class="position-absolute top-0 start-0 w-100 h-100 bg-white opacity-25" style="animation: pulse 2s infinite;"></div>
                    
                    <h4 class="fw-bold position-relative z-1"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>TARGETED MATCH FOUND!</h4>
                    <p class="mb-3 position-relative z-1">The network specifically requested your blood group! You are a match for these active emergencies:</p>
                    
                    <?php foreach($targeted_alerts as $alert): ?>
                        <div class="bg-white bg-opacity-25 rounded-3 p-3 mb-2 position-relative z-1 d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <h6 class="fw-bold mb-1"><?= htmlspecialchars($alert['message']) ?></h6>
                                <small class="text-white-50"><i class="bi bi-clock me-1"></i> Alerted <?= date('h:i A', strtotime($alert['created_at'])) ?></small>
                            </div>
                            <form action="actions/process_donor_checkin.php" method="POST" class="m-0">
                                <input type="hidden" name="request_id" value="<?= $alert['request_id'] ?>">
                                <input type="hidden" name="notification_id" value="<?= $alert['notification_id'] ?>">
                                <button type="submit" class="btn btn-light text-danger fw-bold rounded-pill shadow-sm px-4">RESPOND NOW</button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="fw-bold m-0"><i class="bi bi-broadcast text-danger me-2"></i>Live Alerts Network</h4>
                <div class="d-flex align-items-center gap-2 text-danger fw-bold bg-danger bg-opacity-10 px-3 py-1 rounded-pill small">
                    <span class="pulse-dot"></span> Live
                </div>
            </div>
            
            <div class="row g-3">
                <?php if(empty($active_emergencies)): ?>
                    <div class="col-12"><div class="card bg-success bg-opacity-10 border-success border-opacity-25"><div class="card-body text-center py-5"><i class="bi bi-shield-check text-success fs-1 mb-2 d-block"></i><h5 class="fw-bold text-success m-0">All Clear! No active emergencies right now.</h5></div></div></div>
                <?php else: ?>
                    <?php foreach($active_emergencies as $req): ?>
                        <div class="col-12">
                            <div class="card emergency-card <?= $req['request_type'] == 'Medicine' ? 'medicine' : '' ?>">
                                <div class="card-body p-4 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                    <div>
                                        <?php if($req['request_type'] == 'Blood'): ?>
                                            <h5 class="fw-bold text-danger mb-2"><i class="bi bi-droplet-fill me-1"></i> Needed: <?= htmlspecialchars($req['blood_group']) ?> Blood</h5>
                                        <?php else: ?>
                                            <h5 class="fw-bold text-primary mb-2"><i class="bi bi-capsule me-1"></i> Needed: <?= htmlspecialchars($req['medicine_name']) ?></h5>
                                        <?php endif; ?>
                                        
                                        <div class="d-flex flex-wrap gap-3 text-muted small">
                                            <span><i class="bi bi-hospital me-1"></i> Dr. <?= htmlspecialchars($req['doctor_name'] ?? 'Staff') ?></span>
                                            <span><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> <?= $req['urgency_level'] ?> Urgency</span>
                                            <span><i class="bi bi-clock me-1"></i> <?= date('h:i A', strtotime($req['created_at'])) ?></span>
                                        </div>
                                    </div>
                                    
                                    <form action="actions/process_donor_checkin.php" method="POST" class="m-0">
                                        <input type="hidden" name="request_id" value="<?= $req['request_id'] ?>">
                                        <button type="submit" class="btn btn-<?= $req['request_type'] == 'Blood' ? 'danger' : 'primary' ?> fw-bold px-4 py-2 rounded-pill shadow-sm w-100">
                                            I Can Help <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 20px;">
            <div class="modal-header border-0 bg-light rounded-top-4 pb-0">
                <h5 class="modal-title fw-bold text-dark"><i class="bi bi-person-lines-fill text-danger me-2"></i>Update Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            
            <form action="actions/update_donor_profile.php" method="POST">
                <div class="modal-body p-4 bg-light rounded-bottom-4">
                    
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Full Legal Name</label>
                        <input type="text" name="full_name" class="form-control" value="<?= htmlspecialchars($donor['full_name']) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Blood Group</label>
                        <input type="text" class="form-control bg-secondary bg-opacity-10 text-muted fw-bold border-0" value="<?= htmlspecialchars($donor['blood_group']) ?>" disabled>
                        <div class="form-text small text-danger"><i class="bi bi-lock-fill"></i> Blood group cannot be changed.</div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label fw-bold small text-danger"><i class="bi bi-telephone-fill me-1"></i> Mobile Number (BD Only)</label>
                        <div class="input-group shadow-sm">
                            <span class="input-group-text bg-danger text-white fw-bold border-danger">+880</span>
                            <input type="tel" name="contact_phone" class="form-control border-danger" pattern="1[3-9][0-9]{8}" value="<?= htmlspecialchars(str_replace('+880', '', $donor['contact_phone'])) ?>" maxlength="10" required>
                        </div>
                    </div>

                    <div class="mb-4 bg-white p-3 rounded-3 border">
                        <label class="form-label fw-bold small text-primary mb-3"><i class="bi bi-geo-alt-fill me-1"></i> Update Base Location</label>
                        <button type="button" id="geoBtnUpdate" class="btn btn-outline-primary w-100 mb-3 fw-bold rounded-3 py-2" onclick="getLocationWithGeocoding()">
                            <i class="bi bi-crosshair me-2"></i> Auto-Detect via GPS
                        </button>
                        <div class="text-center mb-3 text-muted small fw-bold">OR CHOOSE MANUALLY</div>
                        <select id="districtSelectUpdate" class="form-select border-primary" required onchange="setCoordinatesManually()">
                            <option value="" disabled selected>Select new area...</option>
                            <option value="Dhaka" data-lat="23.8103" data-lng="90.4125">Dhaka</option>
                            <option value="Chittagong" data-lat="22.3569" data-lng="91.7832">Chittagong</option>
                            <option value="Sylhet" data-lat="24.8949" data-lng="91.8687">Sylhet</option>
                            <option id="gpsOptionUpdate" value="GPS" class="d-none bg-success text-white fw-bold" data-lat="" data-lng="">📍 Detected Area</option>
                        </select>
                        <input type="hidden" name="latitude" id="hiddenLatUpdate" value="<?= $donor['latitude'] ?>" required>
                        <input type="hidden" name="longitude" id="hiddenLngUpdate" value="<?= $donor['longitude'] ?>" required>
                    </div>

                    <button type="submit" class="btn btn-danger w-100 fw-bold py-3 rounded-3 shadow-sm">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Reverse Geocode the Profile Card on Page Load
document.addEventListener("DOMContentLoaded", async function() {
    const lat = <?= $donor['latitude'] ?: '0' ?>;
    const lng = <?= $donor['longitude'] ?: '0' ?>;
    
    if(lat !== 0 && lng !== 0) {
        try {
            const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=10`);
            const data = await res.json();
            const area = data.address.city || data.address.state_district || "Verified Area";
            document.getElementById('displayArea').innerText = area;
        } catch(e) {
            document.getElementById('displayArea').innerText = "Secured Location";
        }
    } else {
        document.getElementById('displayArea').innerText = "Location Not Set";
    }
});

// Update Modal Logic
function setCoordinatesManually() {
    var select = document.getElementById('districtSelectUpdate');
    var option = select.options[select.selectedIndex];
    document.getElementById('hiddenLatUpdate').value = option.getAttribute('data-lat');
    document.getElementById('hiddenLngUpdate').value = option.getAttribute('data-lng');
}
async function getLocationWithGeocoding() {
    const btn = document.getElementById('geoBtnUpdate');
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span> Locating...'; btn.disabled = true;
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(async function(pos) {
            const lat = pos.coords.latitude; const lng = pos.coords.longitude;
            try {
                const res = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=10`);
                const data = await res.json();
                const area = data.address.city || data.address.state_district || "Detected Location";
                
                document.getElementById('hiddenLatUpdate').value = lat;
                document.getElementById('hiddenLngUpdate').value = lng;
                
                const select = document.getElementById('districtSelectUpdate');
                const gpsOpt = document.getElementById('gpsOptionUpdate');
                gpsOpt.value = area; gpsOpt.text = `📍 ${area} (GPS)`;
                gpsOpt.setAttribute('data-lat', lat); gpsOpt.setAttribute('data-lng', lng);
                gpsOpt.classList.remove('d-none'); select.value = area;
                
                btn.innerHTML = `<i class="bi bi-check-circle-fill me-2"></i> Verified: ${area}`;
                btn.classList.replace('btn-outline-primary', 'btn-success'); btn.classList.replace('text-primary', 'text-white');
            } catch(e) {}
        });
    }
}
</script>
</body>
</html>