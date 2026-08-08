<?php
session_start();
// 1. Security Check: Only Admin can delete
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../staff_access.php');
    exit;
}

require '../config/db.php';

if (isset($_GET['id'])) {
    $patient_id = $_GET['id'];

    try {
        // Start a transaction to ensure data integrity
        $pdo->beginTransaction();

        // Step A: Delete from Billing (The table causing your specific error)
        $stmt_bill = $pdo->prepare("DELETE FROM Billing WHERE patient_id = ?");
        $stmt_bill->execute([$patient_id]);

        // Step B: Delete from Consultations
        $stmt_cons = $pdo->prepare("DELETE FROM Consultations WHERE patient_id = ?");
        $stmt_cons->execute([$patient_id]);

        // Step C: Delete from Appointments
        $stmt_app = $pdo->prepare("DELETE FROM Appointments WHERE patient_id = ?");
        $stmt_app->execute([$patient_id]);

        // Step D: Finally delete the Patient record
        $stmt_pat = $pdo->prepare("DELETE FROM Patients WHERE patient_id = ?");
        $stmt_pat->execute([$patient_id]);

        // If everything is successful, commit the changes
        $pdo->commit();

        // Redirect back with a success message
        header('Location: ../admin/manage_patients.php?status=deleted');
        exit;

    } catch (PDOException $e) {
        // If anything goes wrong, cancel all deletions
        $pdo->rollBack();
        error_log("Delete Patient Error: " . $e->getMessage());
        
        // Redirect with an error message
        header('Location: ../admin/manage_patients.php?status=error&msg=Foreign Key Constraint');
        exit;
    }
} else {
    header('Location: ../admin/manage_patients.php');
    exit;
}