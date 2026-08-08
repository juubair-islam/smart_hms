<?php
session_start();
require 'config/db.php';

$error = null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT u.user_id, u.password_hash, u.role, p.is_donor FROM users u JOIN patients p ON u.user_id = p.patient_id WHERE u.username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user && password_verify($password, $user['password_hash'])) {
        if ($user['is_donor'] == 1) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['role'] = 'Donor';
            header("Location: donor_dashboard.php");
            exit();
        } else {
            $error = "This account is not registered as a Donor. Please use the Patient Portal.";
        }
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Donor Login | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>body { background-color: #f8fafc; }</style>
</head>
<body class="d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-5">
            <div class="card border-0 shadow-lg" style="border-radius: 20px; overflow: hidden;">
                <div class="card-header bg-danger text-white text-center py-4 border-0">
                    <i class="bi bi-droplet-fill fs-1"></i>
                    <h4 class="fw-bold mt-2 mb-0">Donor Portal</h4>
                </div>
                <div class="card-body p-5">
                    <?php if ($error): ?>
                        <div class="alert alert-danger small"><i class="bi bi-exclamation-circle me-2"></i><?= $error ?></div>
                    <?php endif; ?>
                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label text-muted small fw-bold">Username</label>
                            <input type="text" name="username" class="form-control form-control-lg bg-light border-0" required>
                        </div>
                        <div class="mb-4">
                            <label class="form-label text-muted small fw-bold">Password</label>
                            <input type="password" name="password" class="form-control form-control-lg bg-light border-0" required>
                        </div>
                        <button type="submit" class="btn btn-danger w-100 py-3 fw-bold rounded-3">Access Dashboard</button>
                    </form>
                    <div class="text-center mt-4">
                        <a href="index.php" class="text-muted text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to Home</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

</body>
</html>