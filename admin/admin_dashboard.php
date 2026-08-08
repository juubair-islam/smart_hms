<?php
session_start();
// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../staff_access.php');
    exit;
}
require '../config/db.php';

try {
    // 2. Summary Totals
    $total_docs = $pdo->query("SELECT COUNT(*) FROM Doctors")->fetchColumn();
    $total_patients = $pdo->query("SELECT COUNT(*) FROM Patients")->fetchColumn();
    $today_apps = $pdo->query("SELECT COUNT(*) FROM Appointments WHERE appointment_date = CURDATE()")->fetchColumn();
    
    // Revenue safety check
    $total_revenue = 0;
    try {
        $revenue_stmt = $pdo->query("SELECT SUM(total_amount) FROM Billing");
        $total_revenue = $revenue_stmt->fetchColumn() ?: 0;
    } catch (PDOException $e) { $total_revenue = 0; }

    // 3. Chart Logic: Patient Growth (Last 6 Months)
    $stmt_growth = $pdo->query("SELECT MONTHNAME(created_at) as m, COUNT(*) as c FROM Patients GROUP BY MONTH(created_at) ORDER BY created_at ASC LIMIT 6");
    $growth_data = $stmt_growth->fetchAll(PDO::FETCH_ASSOC);
    $growth_labels = json_encode(array_column($growth_data, 'm'));
    $growth_values = json_encode(array_column($growth_data, 'c'));

    // 4. Chart Logic: Expertise Distribution
    $stmt_expertise = $pdo->query("SELECT expertise, COUNT(*) as c FROM Doctors GROUP BY expertise");
    $exp_data = $stmt_expertise->fetchAll(PDO::FETCH_ASSOC);
    $exp_labels = json_encode(array_column($exp_data, 'expertise'));
    $exp_values = json_encode(array_column($exp_data, 'c'));

    // 5. Chart Logic: Gender Distribution
    $stmt_gender = $pdo->query("SELECT gender, COUNT(*) as c FROM Patients GROUP BY gender");
    $gen_data = $stmt_gender->fetchAll(PDO::FETCH_ASSOC);
    $gen_labels = json_encode(array_column($gen_data, 'gender'));
    $gen_values = json_encode(array_column($gen_data, 'c'));

    // 6. Recent Activity Feed (With safety for appointment_time)
    $recent_stmt = $pdo->query("SELECT a.*, p.full_name as p_name, d.full_name as d_name 
                                FROM Appointments a 
                                JOIN Patients p ON a.patient_id = p.patient_id 
                                JOIN Doctors d ON a.doctor_id = d.doctor_id 
                                ORDER BY a.appointment_id DESC LIMIT 5");
    $recent_apps = $recent_stmt->fetchAll();

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        :root { --primary-blue: #0077b6; --light-bg: #f8fafc; --nav-dark: #1e293b; }
        body { background-color: var(--light-bg); font-family: 'Segoe UI', system-ui, sans-serif; }
        
        /* Navbar Styling */
        .header-top { background: #fff; padding: 15px 30px; border-bottom: 1px solid #e2e8f0; }
        .sub-nav { background: var(--nav-dark); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        .sub-nav .nav-link { color: #94a3b8; padding: 15px 20px; font-weight: 500; transition: 0.3s; }
        .sub-nav .nav-link:hover, .sub-nav .nav-link.active { color: #fff; background: rgba(255,255,255,0.05); border-bottom: 3px solid var(--primary-blue); }

        /* Card Styling */
        .stat-box { background: #fff; border-radius: 16px; padding: 24px; border: 1px solid #e2e8f0; transition: 0.3s; }
        .stat-box:hover { transform: translateY(-5px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1); }
        .stat-icon { width: 56px; height: 56px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem; }

        .chart-block { background: #fff; border-radius: 16px; padding: 20px; border: 1px solid #e2e8f0; height: 350px; margin-bottom: 24px; }
        .chart-title { font-size: 0.9rem; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 20px; }
        
        .recent-table { background: #fff; border-radius: 16px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 40px; }
    </style>
</head>
<body>

<div class="header-top d-flex justify-content-between align-items-center">
    <div class="d-flex align-items-center">
        <i class="bi bi-shield-lock-fill text-primary fs-3 me-2"></i>
        <h4 class="mb-0 fw-bold" style="color: var(--nav-dark);">Smart HMS <span class="text-primary">Admin</span></h4>
    </div>
    <div class="d-flex align-items-center">
        <div class="text-end me-3">
            <span class="d-block fw-bold small"><?= htmlspecialchars($_SESSION['username']); ?></span>
            <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">SYSTEM ONLINE</span>
        </div>
        <a href="../actions/logout.php" class="btn btn-light btn-sm rounded-pill px-3 border"><i class="bi bi-power text-danger me-1"></i> Logout</a>
    </div>
</div>

<nav class="sub-nav mb-4 sticky-top">
    <div class="container-fluid">
        <ul class="nav">
            <li class="nav-item"><a class="nav-link active" href="admin_dashboard.php"><i class="bi bi-grid-1x2-fill me-2"></i>Overview</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_doctors.php"><i class="bi bi-person-badge-fill me-2"></i>Doctors</a></li>
            <li class="nav-item"><a class="nav-link" href="manage_patients.php"><i class="bi bi-people-fill me-2"></i>Patients</a></li>
            <li class="nav-item"><a class="nav-link" href="view_appointments.php"><i class="bi bi-calendar-check-fill me-2"></i>Appointments</a></li>
            <li class="nav-item"><a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-2"></i>Billing</a></li>
        </ul>
    </div>
</nav>

<div class="container-fluid px-4">
    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div><small class="text-muted fw-bold">DOCTORS</small><h2 class="fw-bold mb-0 mt-1"><?= $total_docs ?></h2></div>
                <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-doctor"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div><small class="text-muted fw-bold">PATIENTS</small><h2 class="fw-bold mb-0 mt-1"><?= $total_patients ?></h2></div>
                <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-people"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div><small class="text-muted fw-bold">TODAY'S VISITS</small><h2 class="fw-bold mb-0 mt-1"><?= $today_apps ?></h2></div>
                <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-calendar2-event"></i></div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="stat-box shadow-sm">
                <div><small class="text-muted fw-bold">REVENUE</small><h2 class="fw-bold mb-0 mt-1">$<?= number_format($total_revenue, 0) ?></h2></div>
                <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-currency-dollar"></i></div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-lg-4">
            <div class="chart-block shadow-sm">
                <div class="chart-title"><i class="bi bi-graph-up me-2"></i> Patient Registration Growth</div>
                <canvas id="growthChart"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-block shadow-sm">
                <div class="chart-title"><i class="bi bi-pie-chart me-2"></i> Department Distribution</div>
                <canvas id="expertiseChart"></canvas>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="chart-block shadow-sm">
                <div class="chart-title"><i class="bi bi-bar-chart me-2"></i> Patient Demographics (Gender)</div>
                <canvas id="genderChart"></canvas>
            </div>
        </div>
    </div>

    <div class="recent-table shadow-sm">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h5 class="fw-bold mb-0"><i class="bi bi-clock-history me-2 text-primary"></i>Recent Appointment Activity</h5>
            <a href="view_appointments.php" class="btn btn-primary btn-sm rounded-pill px-3">View Master List</a>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr class="small text-muted">
                        <th>PATIENT NAME</th>
                        <th>ASSIGNED DOCTOR</th>
                        <th>SCHEDULED AT</th>
                        <th>STATUS</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach($recent_apps as $app): ?>
                    <tr>
                        <td>
                            <div class="fw-bold"><?= htmlspecialchars($app['p_name']) ?></div>
                            <small class="text-muted">ID: #P-<?= $app['patient_id'] ?></small>
                        </td>
                        <td>
                            <div class="fw-medium">Dr. <?= htmlspecialchars($app['d_name']) ?></div>
                        </td>
                        <td>
                            <div class="fw-bold small"><?= date('d M, Y', strtotime($app['appointment_date'])) ?></div>
                            <div class="badge bg-light text-primary border"><?= htmlspecialchars($app['appointment_time'] ?? 'Not Set') ?></div>
                        </td>
                        <td>
                            <?php 
                                $statusColor = match($app['status']) {
                                    'Confirmed', 'Completed' => 'success',
                                    'Cancelled' => 'danger',
                                    default => 'warning'
                                };
                            ?>
                            <span class="badge rounded-pill bg-<?= $statusColor ?>-subtle text-<?= $statusColor ?> border border-<?= $statusColor ?>-subtle px-3">
                                <?= $app['status'] ?>
                            </span>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    // 1. Growth Line Chart
    new Chart(document.getElementById('growthChart'), {
        type: 'line',
        data: {
            labels: <?= $growth_labels ?>,
            datasets: [{
                data: <?= $growth_values ?>,
                borderColor: '#0077b6',
                backgroundColor: 'rgba(0, 119, 182, 0.1)',
                fill: true, tension: 0.4, pointRadius: 5
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } } }
    });

    // 2. Expertise Doughnut Chart
    new Chart(document.getElementById('expertiseChart'), {
        type: 'doughnut',
        data: {
            labels: <?= $exp_labels ?>,
            datasets: [{
                data: <?= $exp_values ?>,
                backgroundColor: ['#0077b6', '#00b4d8', '#90e0ef', '#caf0f8', '#03045e']
            }]
        },
        options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
    });

    // 3. Gender Bar Chart
    new Chart(document.getElementById('genderChart'), {
        type: 'bar',
        data: {
            labels: <?= $gen_labels ?>,
            datasets: [{
                data: <?= $gen_values ?>,
                backgroundColor: ['#0077b6', '#fb7185', '#94a3b8']
            }]
        },
        options: { 
            responsive: true, 
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
        }
    });
</script>

</body>
</html>