<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}

if (isset($_GET['id'])) {
    $appointment_id = $_GET['id'];
    $patient_id = $_SESSION['user_id'];

    try {
        // Only allow cancellation if the appointment belongs to the logged-in patient 
        // and its current status is 'Scheduled'
        $stmt = $pdo->prepare("UPDATE Appointments SET status = 'Cancelled' 
                                WHERE appointment_id = ? AND patient_id = ? AND status = 'Scheduled'");
        $stmt->execute([$appointment_id, $patient_id]);

        if ($stmt->rowCount() > 0) {
            header("Location: ../patient/appointments.php?status=cancelled");
        } else {
            header("Location: ../patient/appointments.php?error=invalid_action");
        }
    } catch (PDOException $e) {
        header("Location: ../patient/appointments.php?error=db_error");
    }
} else {
    header("Location: ../patient/appointments.php");
}
exit;