<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';

$billings = [];
$total_paid = 0;
$total_pending = 0;
$total_invoiced = 0;

try {
    // UPDATED QUERY: Matches your Billing table schema
    // Joining Appointments and Doctors to get the Doctor's name
    $query = "SELECT b.*, p.full_name as patient_name, d.full_name as doctor_name 
              FROM Billing b
              JOIN Patients p ON b.patient_id = p.patient_id
              LEFT JOIN Appointments a ON b.appointment_id = a.appointment_id
              LEFT JOIN Doctors d ON a.doctor_id = d.doctor_id
              ORDER BY b.billing_date DESC";
    
    $stmt = $pdo->query($query);
    $billings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate quick stats using the correct column 'total_payable'
    foreach($billings as $bill) {
        $total_invoiced += $bill['total_payable'];
        if($bill['status'] == 'Paid') {
            $total_paid += $bill['total_payable'];
        } else {
            $total_pending += $bill['total_payable'];
        }
    }
} catch (PDOException $e) {
    $error_msg = "Error: " . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing Management | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-blue: #0077b6;
            --light-bg: #f4f7fe;
            --nav-dark: #1e293b;
        }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', sans-serif; }
        .header-top { background: #fff; padding: 12px 30px; border-bottom: 1px solid #e2e8f0; }
        .sub-nav { background: var(--nav-dark); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1); }
        .sub-nav .nav-link { color: #94a3b8; padding: 15px 20px; font-weight: 500; transition: all 0.3s; border-bottom: 3px solid transparent; }
        .sub-nav .nav-link:hover, .sub-nav .nav-link.active { color: #fff; background: rgba(255,255,255,0.05); border-bottom: 3px solid var(--primary-blue); }
        .stat-card { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; position: relative; overflow: hidden; height: 100%; }
        .stat-card i { position: absolute; right: 20px; top: 20px; font-size: 2rem; opacity: 0.1; }
        .search-area { background: #fff; border-radius: 12px; padding: 15px 25px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
        .table-container { background: #fff; border-radius: 12px; padding: 20px; border: 1px solid #e2e8f0; }
        .status-badge { font-size: 0.75rem; padding: 5px 12px; border-radius: 50px; font-weight: 600; }
        .bg-paid { background-color: #dcfce7; color: #166534; }
        .bg-unpaid { background-color: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>

<div class="header-top d-flex justify-content-between align-items-center">
    <div class="brand">
        <h4 class="mb-0 text-primary fw-bold"><i class="bi bi-shield-plus me-2"></i>Smart Health Care</h4>
    </div>
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
            <li class="nav-item"><a class="nav-link" href="manage_doctors.php"><i class="bi bi-person-badge-fill me-2"></i>Manage Doctors</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_patients.php"><i class="bi bi-people-fill me-2"></i>Manage Patients</a></li>
            <li class="nav-item"><a class="nav-link" href="view_appointments.php"><i class="bi bi-calendar-check-fill me-2"></i>Appointments</a></li>
            <li class="nav-item"><a class="nav-link active" href="billing.php"><i class="bi bi-wallet2 me-2"></i>Billing</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid px-4">
    <?php if(isset($error_msg)): ?>
        <div class="alert alert-danger"><?= $error_msg ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="stat-card shadow-sm border-start border-primary border-4">
                <i class="bi bi-receipt"></i>
                <small class="text-muted fw-bold text-uppercase">Total Invoiced</small>
                <h3 class="fw-bold mb-0 text-primary">$<?= number_format($total_invoiced, 2) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card shadow-sm border-start border-success border-4">
                <i class="bi bi-cash-stack"></i>
                <small class="text-muted fw-bold text-uppercase">Collected Revenue</small>
                <h3 class="fw-bold mb-0 text-success">$<?= number_format($total_paid, 2) ?></h3>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-card shadow-sm border-start border-danger border-4">
                <i class="bi bi-exclamation-octagon"></i>
                <small class="text-muted fw-bold text-uppercase">Pending Payments</small>
                <h3 class="fw-bold mb-0 text-danger">$<?= number_format($total_pending, 2) ?></h3>
            </div>
        </div>
        <div class="col-md-3 text-end d-flex align-items-center justify-content-end">
             <button onclick="window.print()" class="btn btn-primary shadow-sm rounded-pill px-4"><i class="bi bi-printer me-2"></i>Export Report</button>
        </div>
    </div>

    

    <div class="search-area shadow-sm d-flex justify-content-between align-items-center">
        <div class="input-group" style="max-width: 450px;">
            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
            <input type="text" id="billSearch" class="form-control border-start-0" placeholder="Search by Patient or Invoice ID...">
        </div>
        <div class="text-muted small">Showing <?= count($billings) ?> transactions</div>
    </div>

    <div class="table-container shadow-sm">
        <div class="table-responsive">
            <table class="table table-hover align-middle" id="billingTable">
                <thead class="table-light">
                    <tr>
                        <th>Invoice ID</th>
                        <th>Date</th>
                        <th>Patient Details</th>
                        <th>Doctor Details</th>
                        <th>Total Amount</th>
                        <th>Method</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($billings)): ?>
                        <tr><td colspan="7" class="text-center py-5 text-muted">No billing history found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($billings as $bill): ?>
                        <tr>
                            <td class="fw-bold text-primary">#INV-<?= $bill['bill_id'] ?></td>
                            <td><?= date('d M, Y', strtotime($bill['billing_date'])) ?></td>
                            <td>
                                <div class="fw-bold"><?= htmlspecialchars($bill['patient_name']) ?></div>
                                <small class="text-muted">ID: #PAT-<?= $bill['patient_id'] ?></small>
                            </td>
                            <td>
                                <?= $bill['doctor_name'] ? 'Dr. ' . htmlspecialchars($bill['doctor_name']) : '<span class="text-muted small">N/A</span>' ?>
                            </td>
                            <td class="fw-bold">$<?= number_format($bill['total_payable'], 2) ?></td>
                            <td><span class="small text-muted"><?= $bill['payment_method'] ?></span></td>
                            <td>
                                <span class="status-badge <?= $bill['status'] == 'Paid' ? 'bg-paid' : 'bg-unpaid' ?>">
                                    <i class="bi <?= $bill['status'] == 'Paid' ? 'bi-check-circle' : 'bi-clock' ?> me-1"></i>
                                    <?= $bill['status'] ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
document.getElementById('billSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#billingTable tbody tr');
    rows.forEach(row => {
        let content = row.innerText.toLowerCase();
        row.style.display = content.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>