<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register Doctor | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary-blue: #0077b6; --light-bg: #f8f9fa; --nav-dark: #1e293b; }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', sans-serif; }
        .header-top { background: #fff; padding: 12px 30px; border-bottom: 1px solid #e2e8f0; }
        .sub-nav { background: var(--nav-dark); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .sub-nav .nav-link { color: #94a3b8; padding: 15px 20px; font-weight: 500; transition: all 0.3s; }
        .sub-nav .nav-link:hover, .sub-nav .nav-link.active { color: #fff; background: rgba(255,255,255,0.05); border-bottom: 3px solid var(--primary-blue); }
        
        .form-card { background: #fff; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); max-width: 950px; margin: 20px auto; }
        .form-header { background: #fff; padding: 25px; border-bottom: 1px solid #f1f5f9; border-radius: 12px 12px 0 0; }
        .preview-img { width: 140px; height: 140px; object-fit: cover; border-radius: 50%; border: 3px solid var(--light-bg); box-shadow: 0 4px 6px rgba(0,0,0,0.1); display: block; margin-bottom: 15px; }
        
        .section-title { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; color: var(--primary-blue); font-weight: 700; margin-bottom: 20px; display: flex; align-items: center; }
        .section-title::after { content: ""; flex: 1; height: 1px; background: #e2e8f0; margin-left: 15px; }

        .availability-table { background: #f8fafc; border-radius: 8px; border: 1px solid #e2e8f0; }
        .day-row.active-row { background-color: #f0f9ff; }
        .hour-badge { font-size: 0.75rem; padding: 3px 8px; border-radius: 12px; background: #e2e8f0; color: #64748b; font-weight: bold; transition: 0.3s; }
        .hour-badge.active { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show m-3" role="alert">
        <i class="bi bi-exclamation-octagon me-2"></i> <?= $_SESSION['error']; unset($_SESSION['error']); ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<div class="header-top d-flex justify-content-between align-items-center">
    <div class="brand"><h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-plus me-2"></i>Smart Health Care</h4></div>
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

<div class="container pb-5">
    <div class="mb-3">
        <a href="manage_doctors.php" class="text-decoration-none text-muted small fw-bold"><i class="bi bi-arrow-left me-1"></i> Back to Doctor Directory</a>
    </div>

    <div class="form-card">
        <div class="form-header d-flex justify-content-between align-items-center">
            <div>
                <h4 class="mb-0 fw-bold text-dark">Register New Staff</h4>
                <p class="text-muted mb-0 small">Add professional and security details for new medical personnel.</p>
            </div>
            <i class="bi bi-person-plus text-primary display-6 opacity-25"></i>
        </div>
        
        <div class="p-4 p-md-5">
            <form action="../actions/register_doctor.php" method="POST" enctype="multipart/form-data">
                <div class="row g-5">
                    <div class="col-md-4 text-center border-end">
                        <div class="section-title">Profile Photo</div>
                        <img id="imgPreview" src="https://cdn-icons-png.flaticon.com/512/3774/3774299.png" class="preview-img mx-auto" alt="Preview">
                        <div class="px-3 mb-4">
                            <input type="file" name="doctor_image" class="form-control form-control-sm shadow-sm" id="imageInput" accept="image/*" required>
                        </div>

                        <div class="section-title">Security Credentials</div>
                        <div class="text-start mb-3">
                            <label class="form-label fw-semibold small">Portal Username</label>
                            <input type="text" name="username" class="form-control bg-light" placeholder="username_doc" required>
                        </div>
                        <div class="text-start mb-3">
                            <label class="form-label fw-semibold small">Access Password</label>
                            <input type="password" name="password" class="form-control bg-light" placeholder="••••••••" required>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <div class="section-title">Professional Identity</div>
                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <label class="form-label fw-semibold">Full Name</label>
                                <input type="text" name="full_name" class="form-control p-2" placeholder="Dr. Johnathan Smith" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Expertise</label>
                                <select name="expertise" class="form-select p-2" required>
                                    <option value="" selected disabled>Select Department</option>
                                    <option value="Cardiology">Cardiology</option>
                                    <option value="Neurology">Neurology</option>
                                    <option value="General Medicine">General Medicine</option>
                                    <option value="Pediatrics">Pediatrics</option>
                                    <option value="Dental">Dental</option>
                                    <option value="Orthopedics">Orthopedics</option>
                                    <option value="Gynecology">Gynecology</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Qualification</label>
                                <input type="text" name="qualification" class="form-control p-2" placeholder="e.g., MBBS, MD, PhD" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Contact Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bi bi-telephone"></i></span>
                                    <input type="text" name="contact" class="form-control p-2" placeholder="+1..." required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Consultancy Fee</label>
                                <div class="input-group">
                                    <span class="input-group-text">$</span>
                                    <input type="number" name="fees" class="form-control p-2" placeholder="0.00" step="0.01" required>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Gender</label>
                                <div class="d-flex gap-3 pt-2">
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" value="Male" id="m" checked>
                                        <label class="form-check-label" for="m">Male</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="gender" value="Female" id="f">
                                        <label class="form-check-label" for="f">Female</label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="section-title mt-4">Visiting Hours & Availability</div>
                        <div class="availability-table p-3">
                            <table class="table table-sm table-borderless align-middle mb-0">
                                <thead class="text-muted small">
                                    <tr>
                                        <th style="width: 20%;">Day</th>
                                        <th style="width: 15%;">Status</th>
                                        <th>From</th>
                                        <th>To</th>
                                        <th class="text-end">Daily Hours</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                                    foreach($weekdays as $day): 
                                    ?>
                                    <tr class="day-row" id="row-<?= $day ?>">
                                        <td class="fw-bold small"><?= $day ?></td>
                                        <td>
                                            <div class="form-check form-switch">
                                                <input class="form-check-input day-toggle" type="checkbox" name="available_days[]" value="<?= $day ?>" onchange="toggleInputs('<?= $day ?>')">
                                            </div>
                                        </td>
                                        <td><input type="time" name="start_<?= $day ?>" id="start-<?= $day ?>" class="form-control form-control-sm" disabled onchange="calcDuration('<?= $day ?>')"></td>
                                        <td><input type="time" name="end_<?= $day ?>" id="end-<?= $day ?>" class="form-control form-control-sm" disabled onchange="calcDuration('<?= $day ?>')"></td>
                                        <td class="text-end"><span class="hour-badge" id="badge-<?= $day ?>">0 hrs</span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-5 pt-3 border-top d-flex gap-2">
                            <button type="submit" class="btn btn-primary px-5 shadow-sm"><i class="bi bi-check-circle me-2"></i>Complete Registration</button>
                            <button type="reset" class="btn btn-light px-4 border text-muted">Clear Form</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.getElementById('imageInput').onchange = evt => {
        const [file] = document.getElementById('imageInput').files;
        if (file) document.getElementById('imgPreview').src = URL.createObjectURL(file);
    }

    function toggleInputs(day) {
        const isChecked = document.querySelector(`#row-${day} .day-toggle`).checked;
        const start = document.getElementById(`start-${day}`);
        const end = document.getElementById(`end-${day}`);
        const row = document.getElementById(`row-${day}`);
        const badge = document.getElementById(`badge-${day}`);

        if(isChecked) {
            start.disabled = false; end.disabled = false;
            row.classList.add('active-row'); badge.classList.add('active');
        } else {
            start.disabled = true; end.disabled = true;
            start.value = ""; end.value = "";
            row.classList.remove('active-row'); badge.classList.remove('active');
            badge.innerText = "0 hrs";
        }
    }

    function calcDuration(day) {
        const startVal = document.getElementById(`start-${day}`).value;
        const endVal = document.getElementById(`end-${day}`).value;
        const badge = document.getElementById(`badge-${day}`);
        if(startVal && endVal) {
            const s = new Date("2000-01-01 " + startVal);
            const e = new Date("2000-01-01 " + endVal);
            let diff = (e - s) / 1000 / 60 / 60;
            if (diff < 0) diff += 24;
            badge.innerText = diff.toFixed(1) + " hrs";
        }
    }
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>