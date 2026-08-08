<?php
session_start();
require 'config/db.php';

$emergencies = [];
$latest_requests = [];
$donors_list = [];
$db_error = null;

try {
    // 1. Fetch ONLY Active BLOOD emergencies for the map
    $stmt = $pdo->query("SELECT * FROM emergency_requests WHERE status = 'Active' AND request_type = 'Blood'");
    $emergencies = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Fetch the LATEST 5 active BLOOD emergencies for the feed
    $stmt_latest = $pdo->query("
        SELECT e.*, d.full_name AS doctor_name, d.contact_number 
        FROM emergency_requests e
        LEFT JOIN doctors d ON e.requester_id = d.doctor_id
        WHERE e.status = 'Active' AND e.request_type = 'Blood'
        ORDER BY e.created_at DESC
        LIMIT 5
    ");
    $latest_requests = $stmt_latest->fetchAll(PDO::FETCH_ASSOC);

    // 3. Fetch ALL registered donors
    $stmt_donors = $pdo->query("
        SELECT full_name, blood_group, contact_phone, latitude, longitude 
        FROM patients 
        WHERE is_donor = 1 
        ORDER BY full_name ASC
    ");
    $donors_list = $stmt_donors->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    $db_error = "System Notice: Unable to load data. Ensure emergency and donor tables are configured.";
}

$total_emergencies = count($emergencies);
$total_donors = count($donors_list);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Emergency Map | Smart HMS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
    
    <style>
        :root {
            --primary: #0077b6; --accent: #90e0ef; --danger: #e63946; --success: #10b981; --dark: #1e293b; --bg: #f8fafc;
        }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; }
        
        .navbar { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 0; }
        .navbar-brand { font-weight: 800; color: var(--primary) !important; letter-spacing: -0.5px; }
        
        .pulse-dot { width: 12px; height: 12px; background-color: var(--danger); border-radius: 50%; display: inline-block; animation: pulse 1.5s infinite; }
        @keyframes pulse { 0% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0.7); } 70% { box-shadow: 0 0 0 10px rgba(230, 57, 70, 0); } 100% { box-shadow: 0 0 0 0 rgba(230, 57, 70, 0); } }

        .btn-primary { background: var(--primary); border: none; padding: 12px 25px; border-radius: 12px; font-weight: 600; }
        .btn-outline-primary { border-radius: 12px; padding: 12px 25px; font-weight: 600; border-color: var(--primary); color: var(--primary); }
        .btn-outline-primary:hover { background: var(--primary); color: white; }
        
        .map-wrapper { background: white; padding: 10px; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; position: relative; }
        #map { height: 500px; width: 100%; border-radius: 12px; z-index: 1; }

        .scroll-wrapper { height: 440px; overflow-y: auto; padding-right: 5px; }
        .scroll-wrapper::-webkit-scrollbar { width: 6px; }
        .scroll-wrapper::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        
        .data-card { background: white; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.02); transition: 0.2s; border: 1px solid #e2e8f0; }
        .data-card:hover { transform: translateX(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.05); border-color: var(--danger); }
        .request-card { border-left: 4px solid var(--danger); }

        .map-legend { position: absolute; bottom: 20px; left: 20px; background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(5px); padding: 10px 15px; border-radius: 12px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); z-index: 1000; border: 1px solid #e2e8f0; }

        .pulse-icon { background-color: var(--danger); border-radius: 50%; border: 3px solid white; box-shadow: 0 0 15px rgba(230,57,70,0.5); animation: pulse-red 1.5s infinite; }
        .donor-icon { background-color: var(--success); border-radius: 50%; border: 2px solid white; box-shadow: 0 0 8px rgba(16, 185, 129, 0.6); }

        @keyframes pulse-red { 0% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(230,57,70,0.7); } 70% { transform: scale(1.15); box-shadow: 0 0 0 15px rgba(230,57,70,0); } 100% { transform: scale(0.9); box-shadow: 0 0 0 0 rgba(230,57,70,0); } }
        
        .nav-tabs .nav-link { color: var(--dark); font-weight: 600; border: none; padding: 10px 20px; border-radius: 8px 8px 0 0; }
        .nav-tabs .nav-link.active { color: var(--danger); border-bottom: 3px solid var(--danger); background: transparent; }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg sticky-top">
    <div class="container">
        <a class="navbar-brand fs-3" href="index.php">
            <i class="bi bi-heart-pulse-fill me-2"></i>Smart HMS
        </a>
        <div class="d-flex gap-2">
            <a href="patient_access.php" class="btn btn-outline-primary">Patient Portal</a>
            <a href="staff_access.php" class="btn btn-primary"><i class="bi bi-person-badge me-2"></i>Staff Portal</a>
        </div>
    </div>
</nav>

<div class="container py-4">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h2 class="fw-bold m-0"><span class="text-danger">Blood</span> Emergency Radar</h2>
            <p class="text-muted mb-0 fw-bold">
                <i class="bi bi-droplet-fill text-danger me-1"></i> <?= $total_emergencies ?> Active Alerts &nbsp;|&nbsp; 
                <i class="bi bi-people-fill text-success me-1"></i> <?= $total_donors ?> Available Donors
            </p>
        </div>
        
        <div class="d-flex gap-2">
            <a href="request_blood.php" class="btn btn-outline-danger fw-bold rounded-pill shadow-sm px-4 py-2">
                <i class="bi bi-heart-pulse me-2"></i>Request Blood
            </a>
            <a href="donor_register.php" class="btn btn-danger fw-bold rounded-pill shadow-sm px-4 py-2">
                <i class="bi bi-droplet-fill me-2"></i>Register as a Hero
            </a>
        </div>
    </div>
    
    <?php if ($db_error): ?>
        <div class="alert alert-warning border-warning rounded-3"><i class="bi bi-exclamation-triangle-fill me-2"></i><?= $db_error ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-7 col-xl-8">
            <div class="map-wrapper">
                <div id="map"></div>
                <div class="map-legend">
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <div style="width: 12px; height: 12px; background-color: var(--danger); border-radius: 50%;"></div>
                            <span class="small fw-bold text-dark">Blood Needed</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 mt-1 pt-1 border-top">
                            <div style="width: 10px; height: 10px; background-color: var(--success); border-radius: 50%;"></div>
                            <span class="small fw-bold text-muted">Available Donor</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5 col-xl-4">
            <ul class="nav nav-tabs mb-3 border-bottom" id="radarTabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link active" id="requests-tab" data-bs-toggle="tab" data-bs-target="#requests-pane" type="button" role="tab">
                        <i class="bi bi-activity text-danger me-1"></i> Active Alerts
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link" id="donors-tab" data-bs-toggle="tab" data-bs-target="#donors-pane" type="button" role="tab">
                        <i class="bi bi-people-fill text-success me-1"></i> Directory
                    </button>
                </li>
            </ul>

            <div class="tab-content" id="radarTabContent">
                <div class="tab-pane fade show active" id="requests-pane" role="tabpanel">
                    <div class="scroll-wrapper">
                        <?php if (empty($latest_requests)): ?>
                            <div class="text-center p-5 bg-white rounded-3 border">
                                <i class="bi bi-shield-check text-success fs-1 mb-2"></i>
                                <h6 class="text-muted">No active emergencies. The network is secure.</h6>
                            </div>
                        <?php else: ?>
                            <?php foreach ($latest_requests as $req): 
                                $badge_class = ($req['urgency_level'] === 'Critical') ? 'bg-danger' : 'bg-warning text-dark';
                            ?>
                                <div class="data-card request-card p-3 mb-3">
                                    <div class="d-flex justify-content-between align-items-start mb-2">
                                        <h6 class="fw-bold m-0"><i class="bi bi-droplet-fill text-danger me-1"></i> <?= htmlspecialchars($req['blood_group']) ?> Blood</h6>
                                        <span class="badge <?= $badge_class ?>" style="font-size: 0.65rem;"><?= htmlspecialchars($req['urgency_level']) ?></span>
                                    </div>
                                    <hr class="my-2 border-secondary border-opacity-25">
                                    <div class="small text-muted mb-1"><i class="bi bi-hospital me-1"></i> Dr. <?= htmlspecialchars($req['doctor_name'] ?? 'Staff') ?></div>
                                    <div class="small text-muted mb-2"><i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($req['contact_number'] ?? 'N/A') ?></div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="tab-pane fade" id="donors-pane" role="tabpanel">
                    
                    <div class="d-flex gap-2 mb-3">
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-droplet-fill text-danger"></i></span>
                            <select class="form-select border-start-0" id="bloodFilter" onchange="filterDonors()">
                                <option value="ALL">All Blood</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                            </select>
                        </div>
                    </div>
                    
                    <div class="input-group mb-3">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-geo-alt-fill text-primary"></i></span>
                        <select class="form-select border-start-0" id="locationFilter" onchange="filterDonors()">
                            <option value="ALL" data-lat="" data-lng="">All Locations</option>
                            <option value="Dhaka" data-lat="23.8103" data-lng="90.4125">Dhaka (Within 50km)</option>
                            <option value="Chittagong" data-lat="22.3569" data-lng="91.7832">Chittagong (Within 50km)</option>
                            <option value="Sylhet" data-lat="24.8949" data-lng="91.8687">Sylhet (Within 50km)</option>
                            <option value="Rajshahi" data-lat="24.3745" data-lng="88.6042">Rajshahi (Within 50km)</option>
                            <option value="Khulna" data-lat="22.8456" data-lng="89.5403">Khulna (Within 50km)</option>
                            <option value="Barishal" data-lat="22.7010" data-lng="90.3535">Barishal (Within 50km)</option>
                            <option value="Rangpur" data-lat="25.7439" data-lng="89.2752">Rangpur (Within 50km)</option>
                            <option value="Mymensingh" data-lat="24.7471" data-lng="90.4203">Mymensingh (Within 50km)</option>
                        </select>
                    </div>

                    <div class="scroll-wrapper" id="donorList">
                        <?php if (empty($donors_list)): ?>
                            <div class="text-center p-4 bg-white rounded-3 border">
                                <i class="bi bi-person-x text-muted fs-2 mb-2"></i>
                                <h6 class="text-muted">No donors registered yet.</h6>
                            </div>
                        <?php else: ?>
                            <?php foreach ($donors_list as $donor): ?>
                                <div class="data-card p-3 mb-3 donor-item" 
                                     data-blood="<?= htmlspecialchars($donor['blood_group']) ?>"
                                     data-lat="<?= htmlspecialchars($donor['latitude']) ?>"
                                     data-lng="<?= htmlspecialchars($donor['longitude']) ?>">
                                    
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center">
                                            <div class="bg-danger bg-opacity-10 text-danger fw-bold rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 45px; height: 45px; border: 2px solid #e63946;">
                                                <?= htmlspecialchars($donor['blood_group'] ?: 'N/A') ?>
                                            </div>
                                            <div>
                                                <h6 class="fw-bold m-0 text-dark"><?= htmlspecialchars($donor['full_name']) ?></h6>
                                                <span class="small text-muted"><i class="bi bi-telephone-fill me-1"></i> <?= htmlspecialchars($donor['contact_phone']) ?></span>
                                            </div>
                                        </div>
                                        
                                        <a href="request_blood.php" class="btn btn-sm btn-danger fw-bold rounded-pill px-3 shadow-sm">
                                            <i class="bi bi-heart-pulse"></i> Request
                                        </a>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div id="noDonorMatch" class="text-center p-4 bg-white rounded-3 border d-none">
                                <h6 class="text-muted m-0">No donors found in this area.</h6>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
    var map = L.map('map').setView([23.8103, 90.4125], 10);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', { maxZoom: 18 }).addTo(map);

    var markerGroup = L.featureGroup().addTo(map);

    var emergencies = <?= json_encode($emergencies, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var bloodIcon = L.divIcon({ className: 'pulse-icon', iconSize: [20, 20], iconAnchor: [10, 10] });

    if(emergencies.length > 0) {
        emergencies.forEach(function(em) {
            var lat = parseFloat(em.latitude);
            var lng = parseFloat(em.longitude);
            if(!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                var details = `<span class="text-danger fw-bold fs-5">${em.blood_group} Blood</span>`;
                var popupContent = `<div class="text-center p-2"><h6 class="text-muted small">Needed Now:</h6><div class="mb-2">${details}</div></div>`;
                var marker = L.marker([lat, lng], {icon: bloodIcon}).bindPopup(popupContent);
                markerGroup.addLayer(marker);
            }
        });
    }

    var donorsData = <?= json_encode($donors_list, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP); ?>;
    var donorIcon = L.divIcon({ className: 'donor-icon', iconSize: [12, 12], iconAnchor: [6, 6] });

    if(donorsData.length > 0) {
        donorsData.forEach(function(d) {
            var lat = parseFloat(d.latitude);
            var lng = parseFloat(d.longitude);
            if(!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                var popupContent = `<div class="text-center p-1"><h6 class="fw-bold mb-1">${d.full_name}</h6><span class="badge bg-danger">${d.blood_group} Donor</span></div>`;
                var marker = L.marker([lat, lng], {icon: donorIcon}).bindPopup(popupContent);
                markerGroup.addLayer(marker);
            }
        });
    }

    if (markerGroup.getLayers().length > 0) {
        map.fitBounds(markerGroup.getBounds().pad(0.1));
    }

    function getDistanceFromLatLonInKm(lat1, lon1, lat2, lon2) {
        var R = 6371; 
        var dLat = (lat2 - lat1) * (Math.PI/180);
        var dLon = (lon2 - lon1) * (Math.PI/180); 
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1 * (Math.PI/180)) * Math.cos(lat2 * (Math.PI/180)) * Math.sin(dLon/2) * Math.sin(dLon/2); 
        var c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a)); 
        return R * c; 
    }

    function filterDonors() {
        var selectedBlood = document.getElementById('bloodFilter').value;
        var locationSelect = document.getElementById('locationFilter');
        var selectedLocName = locationSelect.value;
        var selectedOpt = locationSelect.options[locationSelect.selectedIndex];
        var filterLat = parseFloat(selectedOpt.getAttribute('data-lat'));
        var filterLng = parseFloat(selectedOpt.getAttribute('data-lng'));

        var donorsList = document.querySelectorAll('.donor-item');
        var visibleCount = 0;

        donorsList.forEach(function(donor) {
            var donorBlood = donor.getAttribute('data-blood');
            var dLat = parseFloat(donor.getAttribute('data-lat'));
            var dLng = parseFloat(donor.getAttribute('data-lng'));
            
            var matchesBlood = (selectedBlood === 'ALL' || donorBlood === selectedBlood);
            
            var matchesLocation = true;
            if (selectedLocName !== 'ALL' && !isNaN(filterLat) && !isNaN(filterLng) && !isNaN(dLat) && !isNaN(dLng)) {
                var dist = getDistanceFromLatLonInKm(filterLat, filterLng, dLat, dLng);
                if (dist > 50) { 
                    matchesLocation = false;
                }
            }

            if (matchesBlood && matchesLocation) {
                donor.classList.remove('d-none');
                visibleCount++;
            } else {
                donor.classList.add('d-none');
            }
        });

        var noMatchMsg = document.getElementById('noDonorMatch');
        if(noMatchMsg) {
            noMatchMsg.classList.toggle('d-none', visibleCount > 0);
        }
    }
</script>
</body>
</html>