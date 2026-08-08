<?php
session_start();
require '../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}

$patient_id = $_SESSION['user_id'];
$bill_id = $_GET['bill_id'] ?? null;

if (!$bill_id) {
    header('Location: appointments.php');
    exit;
}

try {
    // 2. Fetch Bill Details AND Patient Info for auto-filling
    $stmt = $pdo->prepare("SELECT b.*, d.full_name as doctor_name, p.full_name as p_name, p.contact_phone, p.email 
                            FROM Billing b
                            JOIN Patients p ON b.patient_id = p.patient_id
                            JOIN Appointments a ON b.appointment_id = a.appointment_id
                            JOIN Doctors d ON a.doctor_id = d.doctor_id
                            WHERE b.bill_id = ? AND b.patient_id = ?");
    $stmt->execute([$bill_id, $patient_id]);
    $data = $stmt->fetch();

    if (!$data) {
        die("Invalid Invoice or Access Denied.");
    }

    // 3. Handle the Payment Submission (Simulated)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $payment_method = $_POST['payment_method'];

        $update = $pdo->prepare("UPDATE Billing SET 
                                    status = 'Paid', 
                                    payment_method = ?, 
                                    billing_date = NOW() 
                                 WHERE bill_id = ?");
        $update->execute([$payment_method, $bill_id]);

        $_SESSION['payment_success'] = true;
        header("Location: appointments.php");
        exit;
    }

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Secure Checkout | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #0077b6; --soft-bg: #f8faff; }
        body { background-color: var(--soft-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        
        .checkout-container { max-width: 900px; margin: 60px auto; }
        .payment-card { background: white; border-radius: 24px; border: none; box-shadow: 0 20px 40px rgba(0,0,0,0.08); overflow: hidden; }
        
        .order-summary { background: #0077b6; color: white; padding: 40px; }
        .price-display { font-size: 3rem; font-weight: 800; letter-spacing: -1px; }
        
        .form-section { padding: 40px; }
        .input-group-text { background: transparent; border-right: none; color: #94a3b8; }
        .form-control { border-left: none; padding: 12px; border-color: #e2e8f0; }
        .form-control:focus { box-shadow: none; border-color: #0077b6; }
        
        .method-option { border: 2px solid #f1f5f9; border-radius: 15px; padding: 15px; cursor: pointer; transition: 0.3s; margin-bottom: 12px; }
        .btn-check:checked + .method-option { border-color: var(--primary); background: #f0f9ff; }
        
        .locked-badge { font-size: 0.7rem; background: rgba(255,255,255,0.2); padding: 4px 12px; border-radius: 50px; text-transform: uppercase; }
    </style>
</head>
<body>

<div class="container checkout-container">
    <div class="payment-card">
        <div class="row g-0">
            <div class="col-lg-5 order-summary d-flex flex-column justify-content-between">
                <div>
                    <span class="locked-badge"><i class="bi bi-lock-fill me-1"></i> Secure Encryption</span>
                    <h4 class="mt-4 fw-bold">Payment Summary</h4>
                    <p class="opacity-75">Invoice #INV-<?= $bill_id ?></p>
                    
                    <div class="mt-5">
                        <small class="d-block opacity-75">Amount to Pay</small>
                        <div class="price-display">$<?= number_format($data['total_payable'], 2) ?></div>
                    </div>
                </div>

                <div class="mt-5 p-3 rounded-4" style="background: rgba(255,255,255,0.1);">
                    <div class="d-flex align-items-center">
                        <i class="bi bi-person-video3 fs-2 me-3"></i>
                        <div>
                            <div class="small opacity-75">Consulting Doctor</div>
                            <div class="fw-bold">Dr. <?= htmlspecialchars($data['doctor_name']) ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7 form-section">
                <form method="POST">
                    <h5 class="fw-bold mb-4">Billing Information</h5>
                    
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="small fw-bold text-muted">Full Name</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['p_name']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Mobile Number</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['contact_phone']) ?>" readonly>
                        </div>
                        <div class="col-md-6">
                            <label class="small fw-bold text-muted">Email Address</label>
                            <input type="text" class="form-control bg-light" value="<?= htmlspecialchars($data['email']) ?>" readonly>
                        </div>
                    </div>

                    <h5 class="fw-bold mb-3">Payment Method</h5>
                    
                    <div class="mb-4">
                        <input type="radio" class="btn-check" name="payment_method" id="card" value="Card" checked>
                        <label class="method-option d-flex align-items-center w-100" for="card">
                            <i class="bi bi-credit-card-2-front fs-3 me-3 text-primary"></i>
                            <div class="flex-grow-1">
                                <div class="fw-bold">Card Payment</div>
                                <div class="small text-muted">Visa, Mastercard, Amex</div>
                            </div>
                            <i class="bi bi-check-circle-fill text-primary ms-auto"></i>
                        </label>

                        <div class="bg-light p-3 rounded-4 mb-3">
                            <div class="mb-3">
                                <input type="text" class="form-control form-control-sm" placeholder="Card Number" value="4242 4242 4242 4242">
                            </div>
                            <div class="row g-2">
                                <div class="col-6"><input type="text" class="form-control form-control-sm" placeholder="MM/YY" value="12/26"></div>
                                <div class="col-6"><input type="text" class="form-control form-control-sm" placeholder="CVC" value="311"></div>
                            </div>
                        </div>

                        <input type="radio" class="btn-check" name="payment_method" id="online" value="Online">
                        <label class="method-option d-flex align-items-center w-100" for="online">
                            <i class="bi bi-wallet2 fs-3 me-3 text-primary"></i>
                            <div>
                                <div class="fw-bold">Digital Wallet</div>
                                <div class="small text-muted">PayPal / Google Pay</div>
                            </div>
                        </label>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 rounded-pill py-3 fw-bold shadow">
                        Complete Payment
                    </button>
                    
                    <div class="text-center mt-3">
                        <a href="appointments.php" class="text-muted small text-decoration-none">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <div class="text-center mt-4 text-muted small">
        <i class="bi bi-shield-lock-fill"></i> Your data is processed securely. We do not store card details.
    </div>
</div>



</body>
</html>