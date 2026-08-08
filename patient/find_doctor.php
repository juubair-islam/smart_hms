<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

$patient_id = $_SESSION['user_id'];

// 1. Unified Profile Header Data
$stmt = $pdo->prepare("SELECT * FROM Patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();
$dob = new DateTime($patient['date_of_birth']);
$calculated_age = (new DateTime())->diff($dob)->y;

// 2. Search & Filter Logic
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';

$query = "SELECT * FROM Doctors WHERE 1=1";
$params = [];
if ($search) { $query .= " AND full_name LIKE ?"; $params[] = "%$search%"; }
if ($category) { $query .= " AND expertise = ?"; $params[] = $category; }

$doctors = $pdo->prepare($query);
$doctors->execute($params);
$doctor_list = $doctors->fetchAll();

$categories = $pdo->query("SELECT DISTINCT expertise FROM Doctors")->fetchAll(PDO::FETCH_COLUMN);

/**
 * UPDATED LOGIC FOR RE-BOOKING:
 * A doctor is considered "Booked" (disabled) ONLY IF there is an appointment that is:
 * 1. Status is 'Scheduled'
 * 2. AND the Bill is either non-existent or NOT 'Paid'
 * If the appointment is 'Cancelled' or the Bill is 'Paid', the doctor becomes available again.
 */
$stmt_check = $pdo->prepare("
    SELECT a.doctor_id 
    FROM Appointments a
    LEFT JOIN Billing b ON a.appointment_id = b.appointment_id
    WHERE a.patient_id = ? 
    AND a.status = 'Scheduled' 
    AND (b.status IS NULL OR b.status NOT IN ('Paid'))
");
$stmt_check->execute([$patient_id]);
$booked_doctor_ids = $stmt_check->fetchAll(PDO::FETCH_COLUMN);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Find Doctor | Smart Health Care Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #0077b6; --bg: #f4f7fe; --sidebar: #ffffff; }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; }
        
        .sidebar { background: var(--sidebar); min-height: 100vh; border-right: 1px solid #e2e8f0; position: fixed; width: 280px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; }
        .brand-box { padding: 30px 25px; line-height: 1.2; }
        .brand-text-top { font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; }
        .brand-text-bottom { font-size: 0.9rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }

        .nav-link { color: #64748b; padding: 14px 20px; border-radius: 12px; margin: 4px 20px; display: flex; align-items: center; transition: 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: #eff6ff; color: var(--primary); font-weight: 600; }
        
        .profile-header-card { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); margin-bottom: 30px; }
        .search-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 30px; }
        
        .doc-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; transition: 0.3s; height: 100%; display: flex; flex-direction: column; overflow: hidden; }
        .doc-card:hover { transform: translateY(-5px); border-color: var(--primary); box-shadow: 0 10px 20px rgba(0,0,0,0.05); }
        .doc-img { width: 90px; height: 90px; object-fit: cover; border-radius: 50%; border: 4px solid #f0f9ff; margin: 0 auto; }
        
        .prescription-box { background: #f0f9ff; border-left: 4px solid var(--primary); padding: 15px; border-radius: 8px; font-size: 0.85rem; }
        .ai-score-pill { font-size: 0.75rem; font-weight: 700; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 4px 12px; border-radius: 20px; display: inline-block; }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand-box">
        <span class="brand-text-top">Smart Health Care</span>
        <span class="brand-text-bottom">Management System</span>
    </div>
    <nav class="nav flex-column mt-4">
        <a class="nav-link" href="dashboard.php"><i class="bi bi-house-heart me-3"></i> Overview</a>
        <a class="nav-link active" href="appointments.php"><i class="bi bi-calendar-event me-3"></i> My Bookings</a>
        <a class="nav-link" href="medical_records.php"><i class="bi bi-file-earmark-medical me-3"></i> Health Records</a>
        <a class="nav-link" href="ai_symptom_checker.php"><i class="bi bi-cpu me-3"></i> AI Disease Checker</a>
        <a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-3"></i> Billing</a>
        <div class="mt-auto px-4 pt-5"><hr></div>
        <a class="nav-link text-danger mb-4" href="../actions/logout.php"><i class="bi bi-power me-3"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <?php if(isset($_GET['status']) && $_GET['status'] == 'success'): ?>
        <div class="alert alert-success border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-3 fs-4"></i>
            <div>
                <div class="fw-bold">Booking Confirmed!</div>
                <div class="small">Your appointment has been successfully scheduled.</div>
            </div>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="alert alert-danger border-0 rounded-4 shadow-sm mb-4 d-flex align-items-center">
            <i class="bi bi-exclamation-triangle-fill me-3 fs-4"></i>
            <div>
                <div class="fw-bold">Booking Unsuccessful</div>
                <div class="small">
                    <?php 
                        if($_GET['error'] == 'slot_taken') echo 'This time slot is already taken. Please pick another time.';
                        elseif($_GET['error'] == 'duplicate_booking') echo 'You have an active/unpaid appointment with this doctor. Cancel it or complete payment to book again.';
                        else echo 'An error occurred. Please try again.';
                    ?>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="profile-header-card d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-dark mb-1"><?= strtoupper(htmlspecialchars($patient['full_name'])) ?></h3>
            <div class="d-flex gap-3 align-items-center text-muted small">
                <span><i class="bi bi-person"></i> <?= $calculated_age ?> Years • <?= $patient['gender'] ?></span>
                <span>|</span>
                <span><i class="bi bi-telephone"></i> <?= htmlspecialchars($patient['contact_phone']) ?></span>
            </div>
        </div>
        <a href="appointments.php" class="btn btn-outline-primary rounded-pill px-4">
            <i class="bi bi-calendar3 me-2"></i> My Appointments
        </a>
    </div>

    <div class="search-card">
        <h5 class="fw-bold mb-3">Filter Specialists</h5>
        <form method="GET" class="row g-3">
            <div class="col-md-5">
                <div class="input-group">
                    <span class="input-group-text bg-light border-0"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control bg-light border-0" placeholder="Search by name..." value="<?= htmlspecialchars($search) ?>">
                </div>
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select bg-light border-0">
                    <option value="">All Specializations</option>
                    <?php foreach($categories as $cat): ?>
                        <option value="<?= $cat ?>" <?= $category == $cat ? 'selected' : '' ?>><?= $cat ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <button type="submit" class="btn btn-primary w-100 rounded-3 fw-bold">Apply Filters</button>
            </div>
        </form>
    </div>

    <h4 class="fw-bold mb-4">Available Doctors</h4>

    <div class="row g-4">
        <?php foreach($doctor_list as $doc): ?>
        <div class="col-xl-4 col-md-6">
            <div class="doc-card p-4">
                <div class="text-center mb-3">
                    <img src="../<?= htmlspecialchars($doc['image_url'] ?: 'assets/img/default_doc.jpg') ?>" class="doc-img mb-3" alt="Doctor">
                    <h5 class="fw-bold mb-1">Dr. <?= htmlspecialchars($doc['full_name']) ?></h5>
                    <div class="ai-score-pill mb-2"><?= htmlspecialchars($doc['expertise']) ?></div>
                </div>
                
                <div class="prescription-box mt-2 mb-4">
                    <div class="fw-bold text-primary mb-1 small uppercase">Clinic Availability</div>
                    <div class="text-dark"><i class="bi bi-clock me-2"></i><?= htmlspecialchars($doc['availability'] ?: 'Mon-Fri: 09:00 - 17:00') ?></div>
                </div>

                <?php if(in_array($doc['doctor_id'], $booked_doctor_ids)): ?>
                    <button class="btn btn-secondary w-100 rounded-pill mt-auto fw-bold py-2" disabled title="Complete current session or cancel to book again">
                        <i class="bi bi-calendar-check me-2"></i>Already Booked
                    </button>
                <?php else: ?>
                    <button class="btn btn-primary w-100 rounded-pill mt-auto fw-bold py-2" 
                            data-bs-toggle="modal" data-bs-target="#bookModal<?= $doc['doctor_id'] ?>">
                        Request Appointment
                    </button>
                <?php endif; ?>
            </div>
        </div>

        <div class="modal fade" id="bookModal<?= $doc['doctor_id'] ?>" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content border-0 rounded-4 shadow">
                    <div class="modal-header border-0 p-4 pb-0">
                        <h5 class="fw-bold">Schedule Consultation</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="../actions/process_booking.php" method="POST">
                        <div class="modal-body p-4">
                            <div class="alert alert-primary border-0 rounded-3 mb-4">
                                <small class="d-block fw-bold text-uppercase mb-1">Doctor's Official Schedule:</small>
                                <div class="fw-bold"><i class="bi bi-calendar-check me-2"></i><?= htmlspecialchars($doc['availability'] ?: 'Mon-Fri: 09:00 - 17:00') ?></div>
                            </div>

                            <input type="hidden" name="doctor_id" value="<?= $doc['doctor_id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">SELECT DATE</label>
                                <input type="date" name="app_date" class="form-control rounded-3" 
                                       min="<?= date('Y-m-d') ?>" 
                                       max="<?= date('Y-m-d', strtotime('+30 days')) ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">SELECT TIME</label>
                                <input type="time" name="app_time" class="form-control rounded-3" required>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-bold text-muted">REASON FOR VISIT</label>
                                <textarea name="reason" class="form-control rounded-3" rows="2" placeholder="Briefly describe your symptoms..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer border-0 p-4 pt-0">
                            <button type="submit" class="btn btn-primary w-100 py-3 fw-bold rounded-pill">Confirm & Pay Later</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>