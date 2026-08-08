<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';
$patient_id = $_SESSION['user_id'];

try {
    // 1. Unified Profile Header Data
    $stmt = $pdo->prepare("SELECT * FROM Patients WHERE patient_id = ?");
    $stmt->execute([$patient_id]);
    $patient = $stmt->fetch();
    
    $dob = new DateTime($patient['date_of_birth']);
    $calculated_age = (new DateTime())->diff($dob)->y;

    // 2. Fetch Billing Statistics for the header area
    $stmt_stats = $pdo->prepare("SELECT 
        SUM(CASE WHEN status = 'Pending' THEN total_payable ELSE 0 END) as total_due,
        SUM(CASE WHEN status = 'Paid' THEN total_payable ELSE 0 END) as total_paid
        FROM Billing WHERE patient_id = ?");
    $stmt_stats->execute([$patient_id]);
    $stats = $stmt_stats->fetch();

    // 3. Fetch Unified Billing & Insurance Records
    $stmt_bills = $pdo->prepare("SELECT b.*, d.full_name as d_name, d.expertise, ic.claim_status, ic.provider_name 
                                    FROM Billing b 
                                    LEFT JOIN Doctors d ON b.appointment_id = d.doctor_id 
                                    LEFT JOIN InsuranceClaims ic ON b.bill_id = ic.bill_id
                                    WHERE b.patient_id = ? 
                                    ORDER BY b.billing_date DESC");
    $stmt_bills->execute([$patient_id]);
    $bills = $stmt_bills->fetchAll();

} catch (PDOException $e) { 
    die("Error: " . $e->getMessage()); 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Billing & Insurance | Smart Health Care Management System</title>
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
        
        /* Billing Entry Styling (Matches Record Entry) */
        .billing-entry { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; transition: 0.3s; }
        .billing-entry:hover { border-color: var(--primary); box-shadow: 0 10px 20px rgba(0,0,0,0.03); }
        
        .date-badge { width: 70px; height: 70px; background: #f8fafc; border-radius: 15px; display: flex; flex-direction: column; align-items: center; justify-content: center; border: 1px solid #e2e8f0; }
        
        .amount-box { background: #f0f9ff; border-left: 4px solid var(--primary); padding: 15px; border-radius: 8px; }
        .status-pill { font-size: 0.75rem; font-weight: 700; padding: 4px 12px; border-radius: 20px; text-transform: uppercase; }
        .status-paid { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .status-pending { background: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
        
        .insurance-tag { font-size: 0.75rem; font-weight: 600; background: #eff6ff; color: #1e40af; padding: 4px 10px; border-radius: 6px; border: 1px solid #bfdbfe; }
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
        <a class="nav-link" href="appointments.php"><i class="bi bi-calendar-event me-3"></i> My Bookings</a>
        <a class="nav-link" href="medical_records.php"><i class="bi bi-file-earmark-medical me-3"></i> Health Records</a>
        <a class="nav-link" href="ai_symptom_checker.php"><i class="bi bi-cpu me-3"></i> AI Disease Checker</a>
        <a class="nav-link active" href="billing.php"><i class="bi bi-wallet2 me-3"></i> Billing</a>
        <div class="mt-auto px-4 pt-5"><hr></div>
        <a class="nav-link text-danger mb-4" href="../actions/logout.php"><i class="bi bi-power me-3"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <div class="profile-header-card d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-dark mb-1"><?= strtoupper(htmlspecialchars($patient['full_name'])) ?></h3>
            <div class="d-flex gap-3 align-items-center text-muted small">
                <span><i class="bi bi-person"></i> <?= $calculated_age ?> Years • <?= $patient['gender'] ?></span>
                <span>|</span>
                <span><i class="bi bi-cash-stack"></i> Due: <strong>$<?= number_format($stats['total_due'] ?? 0, 2) ?></strong></span>
            </div>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary rounded-pill px-4 shadow-sm" onclick="location.reload()">
                <i class="bi bi-arrow-clockwise me-2"></i> Refresh
            </button>
        </div>
    </div>

    <h4 class="fw-bold mb-4">Billing & Invoices</h4>

    <?php if(empty($bills)): ?>
        <div class="text-center py-5 bg-white rounded-4 shadow-sm">
            <i class="bi bi-receipt display-1 text-muted opacity-25"></i>
            <p class="mt-3 text-muted">No invoices generated yet.</p>
        </div>
    <?php else: ?>
        <?php foreach($bills as $bill): ?>
            <div class="billing-entry">
                <div class="row align-items-center">
                    <div class="col-md-auto text-center">
                        <div class="date-badge mb-0">
                            <span class="fw-bold text-primary fs-4"><?= date('d', strtotime($bill['billing_date'])) ?></span>
                            <span class="text-muted small text-uppercase fw-bold"><?= date('M', strtotime($bill['billing_date'])) ?></span>
                        </div>
                    </div>
                    
                    <div class="col">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="fw-bold mb-0">Invoice #INV-<?= $bill['bill_id'] ?></h5>
                                <span class="text-muted small">Service: Consultation with Dr. <?= htmlspecialchars($bill['d_name'] ?? 'Medical Staff') ?></span>
                            </div>
                            <span class="status-pill <?= $bill['status'] == 'Paid' ? 'status-paid' : 'status-pending' ?>">
                                <i class="bi <?= $bill['status'] == 'Paid' ? 'bi-check-circle' : 'bi-clock' ?> me-1"></i>
                                <?= $bill['status'] ?>
                            </span>
                        </div>

                        <div class="row mt-3 align-items-end">
                            <div class="col-md-6">
                                <div class="amount-box">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="text-muted small fw-bold text-uppercase">Total Payable</span>
                                        <h4 class="fw-bold text-dark mb-0">$<?= number_format($bill['total_payable'], 2) ?></h4>
                                    </div>
                                    <div class="mt-2 pt-2 border-top border-secondary border-opacity-10 d-flex gap-2">
                                        <?php if($bill['provider_name']): ?>
                                            <span class="insurance-tag">
                                                <i class="bi bi-shield-check me-1"></i> Insurance: <?= htmlspecialchars($bill['provider_name']) ?> (<?= $bill['claim_status'] ?>)
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6 text-end">
                                <div class="btn-group shadow-sm rounded-pill overflow-hidden">
                                    <button class="btn btn-outline-secondary btn-sm px-3 border-end-0">
                                        <i class="bi bi-download me-1"></i> PDF
                                    </button>
                                    <?php if(!$bill['claim_status'] && $bill['status'] !== 'Paid'): ?>
                                        <button class="btn btn-outline-primary btn-sm px-3" onclick="openClaimModal(<?= $bill['bill_id'] ?>)">
                                            <i class="bi bi-shield-plus me-1"></i> Claim
                                        </button>
                                    <?php endif; ?>
                                    <?php if($bill['status'] !== 'Paid'): ?>
                                        <button class="btn btn-primary btn-sm px-4">
                                            Pay Now <i class="bi bi-arrow-right ms-1"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="claimModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 border-0 shadow">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="fw-bold">Submit Insurance Claim</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="submit_claim.php" method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="bill_id" id="modal_bill_id">
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Insurance Provider</label>
                        <select name="provider" class="form-select border-light-subtle rounded-3" required>
                            <option value="">Select Provider</option>
                            <option>MetLife</option>
                            <option>Prudential Health</option>
                            <option>BlueCross BlueShield</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Policy Number</label>
                        <input type="text" name="policy_no" class="form-control border-light-subtle rounded-3" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted text-uppercase">Upload Card/Document</label>
                        <input type="file" name="claim_doc" class="form-control border-light-subtle rounded-3" required>
                    </div>
                </div>
                <div class="modal-footer border-top-0 pt-0">
                    <button type="submit" class="btn btn-primary w-100 rounded-pill fw-bold py-2">Submit Claim Application</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function openClaimModal(billId) {
    document.getElementById('modal_bill_id').value = billId;
    var myModal = new bootstrap.Modal(document.getElementById('claimModal'));
    myModal.show();
}
</script>
</body>
</html>