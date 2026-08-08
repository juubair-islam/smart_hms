<?php
session_start();
require 'config/db.php';

// Quick check if they are logged in as a Patient. If not, redirect to login.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header("Location: patient_access.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Request Blood | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { background-color: #f8fafc; font-family: 'Segoe UI', sans-serif;}
        .form-control:focus, .form-select:focus {
            border-color: #e63946;
            box-shadow: 0 0 0 0.25rem rgba(230, 57, 70, 0.25);
        }
    </style>
</head>
<body class="d-flex flex-column min-vh-100">

<nav class="navbar bg-white border-bottom py-3 mb-5">
    <div class="container">
<a href="patient/dashboard.php" class="btn btn-outline-secondary btn-sm fw-bold rounded-pill">
    <i class="bi bi-arrow-left me-1"></i> Back to Dashboard
</a>
        <span class="navbar-brand fs-4 fw-bold text-danger m-0"><i class="bi bi-heart-pulse-fill me-2"></i>Emergency Network</span>
    </div>
</nav>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-7 col-lg-6">
            <div class="card border-0 shadow-lg" style="border-radius: 20px;">
                <div class="card-header bg-danger text-white text-center py-4 border-0 rounded-top-4">
                    <h3 class="fw-bold m-0"><i class="bi bi-droplet-fill me-2"></i>Request Blood</h3>
                </div>
                <div class="card-body p-4 p-md-5">
                    <p class="text-muted small text-center mb-4">This will instantly notify all available donors matching this blood group who are near your location.</p>
                    
                    <form action="actions/process_blood_request.php" method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">Required Blood Group</label>
                            <select name="blood_group" class="form-select border-danger" required>
                                <option value="" disabled selected>Select Blood Group...</option>
                                <option value="A+">A+</option><option value="A-">A-</option>
                                <option value="B+">B+</option><option value="B-">B-</option>
                                <option value="O+">O+</option><option value="O-">O-</option>
                                <option value="AB+">AB+</option><option value="AB-">AB-</option>
                            </select>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">Urgency Level</label>
                            <select name="urgency_level" class="form-select" required>
                                <option value="High">High (Within 24 Hours)</option>
                                <option value="Critical">Critical (Immediately!)</option>
                            </select>
                        </div>

                        <div class="mb-4 bg-light p-4 rounded-3 border border-secondary border-opacity-10">
                            <label class="form-label fw-bold small text-danger mb-3"><i class="bi bi-geo-alt-fill me-1"></i> Hospital / Patient Location</label>
                            
                            <button type="button" id="geoBtn" class="btn btn-outline-danger w-100 mb-3 fw-bold rounded-3 py-2" onclick="getLocationWithGeocoding()">
                                <i class="bi bi-crosshair me-2"></i> Auto-Detect via GPS
                            </button>
                            
                            <div class="text-center mb-3 text-muted small fw-bold">OR CHOOSE MANUALLY</div>

                            <select id="districtSelect" class="form-select border-danger" required onchange="setCoordinatesManually()">
                                <option value="" disabled selected>Select Hospital District / Area...</option>
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

                        <button type="submit" class="btn btn-danger w-100 py-3 fw-bold shadow-sm rounded-3 fs-5">
                            <i class="bi bi-broadcast me-2"></i> Broadcast to Donors
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function setCoordinatesManually() {
    var select = document.getElementById('districtSelect');
    var selectedOption = select.options[select.selectedIndex];
    
    document.getElementById('hiddenLat').value = selectedOption.getAttribute('data-lat');
    document.getElementById('hiddenLng').value = selectedOption.getAttribute('data-lng');
}

async function getLocationWithGeocoding() {
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
                btn.innerHTML = '<i class="bi bi-crosshair me-2"></i> Auto-Detect via GPS';
                btn.disabled = false;
                alert("Location access denied. Please choose your district manually from the list.");
            }
        );
    } else {
        alert("Geolocation is not supported by this browser.");
        btn.innerHTML = '<i class="bi bi-crosshair me-2"></i> Auto-Detect via GPS';
        btn.disabled = false;
    }
}
</script>
</body>
</html>