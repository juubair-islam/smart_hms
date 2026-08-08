<?php
session_start();
// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../index.php');
    exit;
}

require '../config/db.php';

if (isset($_GET['id'])) {
    $doctor_id = $_GET['id'];

    try {
        // Start transaction to ensure data integrity
        $pdo->beginTransaction();

        // 1. Get image path to delete the file from the server
        $stmt = $pdo->prepare("SELECT image_url FROM Doctors WHERE doctor_id = ?");
        $stmt->execute([$doctor_id]);
        $doctor = $stmt->fetch();

        if ($doctor && !empty($doctor['image_url']) && $doctor['image_url'] != 'assets/img/default_doc.jpg') {
            $full_path = '../' . $doctor['image_url'];
            if (file_exists($full_path)) {
                unlink($full_path); 
            }
        }

        // 2. MANUALLY CLEAN UP DEPENDENCIES (To avoid Foreign Key errors)
        // Delete investigations linked to medical records of this doctor
        $pdo->prepare("DELETE FROM Investigations WHERE record_id IN (SELECT record_id FROM MedicalRecords WHERE doctor_id = ?)")->execute([$doctor_id]);
        
        // Delete prescriptions linked to consultations of this doctor
        $pdo->prepare("DELETE FROM Prescriptions WHERE consultation_id IN (SELECT consultation_id FROM Consultations WHERE doctor_id = ?)")->execute([$doctor_id]);

        // Delete Medical Records & Consultations
        $pdo->prepare("DELETE FROM MedicalRecords WHERE doctor_id = ?")->execute([$doctor_id]);
        $pdo->prepare("DELETE FROM Consultations WHERE doctor_id = ?")->execute([$doctor_id]);

        // Delete Appointments (This is the one causing your specific error)
        $pdo->prepare("DELETE FROM Appointments WHERE doctor_id = ?")->execute([$doctor_id]);

        // 3. Delete the User. 
        // Now that appointments are gone, this will successfully delete the row in Doctors too.
        $deleteStmt = $pdo->prepare("DELETE FROM Users WHERE user_id = ?");
        $deleteStmt->execute([$doctor_id]);

        $pdo->commit();
        $_SESSION['success'] = "Doctor and all associated records (appointments, prescriptions) removed successfully.";

    } catch (PDOException $e) {
        $pdo->rollBack();
        // Friendly error message
        $_SESSION['error'] = "Database Error: " . $e->getMessage();
    }
} else {
    $_SESSION['error'] = "Invalid Request. No ID provided.";
}

header("Location: ../admin/manage_doctors.php");
exit;