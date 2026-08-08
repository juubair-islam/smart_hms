<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header('Location: ../staff_access.php');
    exit;
}
require '../config/db.php';
$doctor_id = $_SESSION['user_id'];

// Get date from filter or default to today
$selected_date = isset($_GET['filter_date']) ? $_GET['filter_date'] : date('Y-m-d');

try {
    // 1. Fetch Doctor Profile
    $stmt = $pdo->prepare("SELECT * FROM Doctors WHERE doctor_id = ?");
    $stmt->execute([$doctor_id]);
    $doctor = $stmt->fetch();

    // 2. Fetch Daily Stats
    // Total Taka Earned (Paid)
    $stmt_income = $pdo->prepare("SELECT SUM(b.amount) FROM Billing b 
                                 JOIN Appointments a ON b.appointment_id = a.appointment_id 
                                 WHERE a.doctor_id = ? AND a.appointment_date = ? AND b.status = 'Paid'");
    $stmt_income->execute([$doctor_id, $selected_date]);
    $daily_income = $stmt_income->fetchColumn() ?: 0;

    // Total Taka Due (Pending)
    $stmt_due = $pdo->prepare("SELECT SUM(b.amount) FROM Billing b 
                              JOIN Appointments a ON b.appointment_id = a.appointment_id 
                              WHERE a.doctor_id = ? AND a.appointment_date = ? AND b.status = 'Pending'");
    $stmt_due->execute([$doctor_id, $selected_date]);
    $daily_due = $stmt_due->fetchColumn() ?: 0;

    // 3. Fetch Appointment List for the selected day
    $stmt_list = $pdo->prepare("SELECT a.*, p.full_name as p_name, p.gender, b.status as bill_status, b.amount
                                FROM Appointments a 
                                JOIN Patients p ON a.patient_id = p.patient_id 
                                LEFT JOIN Billing b ON a.appointment_id = b.appointment_id
                                WHERE a.doctor_id = ? 
                                AND a.appointment_date = ?
                                AND a.status != 'Cancelled'
                                ORDER BY a.appointment_id ASC"); 
    $stmt_list->execute([$doctor_id, $selected_date]);
    $day_appointments = $stmt_list->fetchAll();

} catch (PDOException $e) {
    error_log("Appointment Page Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointments | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --medical-blue: #0077b6;
            --medical-light: #f0f9ff;
            --dark-navy: #1e293b;
            --sidebar-width: 280px;
        }

        body { background-color: #f8fafc; font-family: 'Segoe UI', system-ui, sans-serif; overflow-x: hidden; }

        .sidebar {
            width: var(--sidebar-width);
            height: 100vh;
            position: fixed;
            background: #ffffff;
            border-right: 1px solid #e2e8f0;
            transition: all 0.3s;
            z-index: 1000;
        }

        .brand-area { padding: 30px 25px; border-bottom: 1px solid #f1f5f9; }
        .brand-title { font-size: 1.1rem; line-height: 1.2; font-weight: 800; color: var(--dark-navy); text-transform: uppercase; letter-spacing: -0.5px; }

        .role-badge {
            background: #dcfce7; color: #166534; padding: 4px 12px; border-radius: 6px;
            font-size: 0.7rem; font-weight: 800; display: inline-block; margin-top: 5px; border: 1px solid #bbf7d0;
        }

        .nav-link {
            margin: 5px 15px; padding: 12px 15px; border-radius: 10px; color: #64748b;
            font-weight: 500; display: flex; align-items: center; transition: 0.2s; text-decoration: none;
        }

        .nav-link:hover, .nav-link.active { background: var(--medical-light); color: var(--medical-blue); }

        .main-wrapper { margin-left: var(--sidebar-width); padding: 40px; }

        .glass-card {
            background: white; border: 1px solid #e2e8f0; border-radius: 20px;
            padding: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.02); transition: transform 0.2s;
        }

        .icon-circle {
            width: 54px; height: 54px; border-radius: 14px;
            display: flex; align-items: center; justify-content: center; font-size: 1.5rem;
        }

        .btn-consult {
            background: var(--medical-blue); color: white; border-radius: 10px;
            font-weight: 600; padding: 8px 20px; border: none; text-decoration: none; transition: 0.3s;
        }
        .btn-consult:hover { background: #005f91; color: white; }

        .date-filter-box {
            background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 20px; margin-bottom: 30px;
        }
    </style>
</head>
<body>

<aside class="sidebar">
    <div class="brand-area">
        <div class="d-flex align-items-center">
            <i class="bi bi-person-heart text-primary fs-2 me-3"></i>
            <div>
                <div class="brand-title text-primary">Smart HCMS</div> </div>
        </div>
    </div>
    
    <div class="mt-4">
        <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill me-3"></i> Dashboard</a>
        <a href="appointments.php" class="nav-link active"><i class="bi bi-calendar2-week me-3"></i> Appointments</a>
        <a href="patients.php" class="nav-link"><i class="bi bi-person-lines-fill me-3"></i> Patient Records</a>
        <a href="prescriptions.php" class="nav-link"><i class="bi bi-capsule me-3"></i> Prescriptions</a>
        
        <div style="margin-top: 150px;">
            <a href="../actions/logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left me-3"></i> Logout</a>
        </div>
    </div>
</aside>

<main class="main-wrapper">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold">Appointment Records</h2>
        <form action="" method="GET" class="d-flex gap-2 bg-white p-2 rounded-4 shadow-sm border">
            <input type="date" name="filter_date" class="form-control border-0" value="<?= $selected_date ?>">
            <button type="submit" class="btn btn-primary rounded-3 px-4">Filter</button>
        </form>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="glass-card d-flex align-items-center">
                <div class="icon-circle bg-primary-subtle text-primary me-3">
                    <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0"><?= count($day_appointments) ?></h3>
                    <p class="text-muted small mb-0">Appointments Today</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card d-flex align-items-center">
                <div class="icon-circle bg-success-subtle text-success me-3">
                    <i class="bi bi-cash-stack"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0">৳<?= number_format($daily_income, 2) ?></h3>
                    <p class="text-muted small mb-0">Total Income (Paid)</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="glass-card d-flex align-items-center">
                <div class="icon-circle bg-danger-subtle text-danger me-3">
                    <i class="bi bi-exclamation-triangle"></i>
                </div>
                <div>
                    <h3 class="fw-bold mb-0">৳<?= number_format($daily_due, 2) ?></h3>
                    <p class="text-muted small mb-0">Total Due (Pending)</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <h4 class="fw-bold mb-4">Appointment Schedule</h4>
            
            <?php if(empty($day_appointments)): ?>
                <div class="glass-card text-center py-5">
                    <i class="bi bi-calendar-x text-muted display-4 mb-3"></i>
                    <h5 class="fw-bold">No Records Found</h5>
                    <p class="text-muted">There are no appointments scheduled for this date.</p>
                </div>
            <?php else: ?>
                <?php foreach($day_appointments as $app): ?>
                    <div class="glass-card mb-3 d-flex align-items-center justify-content-between border-start border-4 <?= $app['bill_status'] == 'Paid' ? 'border-success' : 'border-warning' ?>">
                        <div class="d-flex align-items-center">
                            <div class="bg-light rounded-3 p-3 text-primary fw-bold me-3">
                                <?= substr($app['p_name'], 0, 1) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?= htmlspecialchars($app['p_name']) ?></h6>
                                <small class="text-muted"><?= $app['gender'] ?> • Patient ID: #P-<?= $app['patient_id'] ?></small>
                            </div>
                        </div>
                        
                        <div class="d-flex align-items-center gap-5">
                            <div class="text-center">
                                <p class="small text-muted mb-0">Fee</p>
                                <span class="fw-bold text-dark">৳<?= number_format($app['amount'], 2) ?></span>
                            </div>
                            
                            <div class="text-center">
                                <p class="small text-muted mb-0">Payment</p>
                                <?php if($app['bill_status'] == 'Paid'): ?>
                                    <span class="badge rounded-pill bg-success-subtle text-success px-3">Paid</span>
                                <?php else: ?>
                                    <span class="badge rounded-pill bg-warning-subtle text-warning px-3">Pending</span>
                                <?php endif; ?>
                            </div>

                            <a href="consult_patient.php?id=<?= $app['appointment_id'] ?>" class="btn-consult">View Details</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</main>

</body>
</html>