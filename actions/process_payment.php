<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['bill_id'])) {
    $bill_id = $_POST['bill_id'];

    try {
        // Update the billing status to 'Paid'
        $stmt = $pdo->prepare("UPDATE Billing SET status = 'Paid', payment_method = 'Card' WHERE bill_id = ?");
        $stmt->execute([$bill_id]);

        // Success redirect
        header("Location: ../patient/appointments.php?status=success");
        exit;
    } catch (PDOException $e) {
        header("Location: ../patient/appointments.php?error=payment_failed");
        exit;
    }
} else {
    header("Location: ../patient/appointments.php");
    exit;
}