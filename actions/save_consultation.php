<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header('Location: ../staff_access.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id = $_SESSION['user_id'];
    $appointment_id = $_POST['appointment_id'];
    $patient_id = $_POST['patient_id'];
    $symptoms = $_POST['symptoms'] ?? '';
    $diagnosis = $_POST['diagnosis'] ?? '';
    $advice = $_POST['advice'] ?? '';

    $medicines = $_POST['medicine'] ?? [];
    $dosages = $_POST['dosage'] ?? [];
    $durations = $_POST['duration'] ?? [];

    try {
        $pdo->beginTransaction();

        // 1. Insert into Consultations (Matches your schema)
        $sql_cons = "INSERT INTO Consultations (appointment_id, patient_id, doctor_id, symptoms, diagnosis, advice) 
                     VALUES (?, ?, ?, ?, ?, ?)";
        $stmt_cons = $pdo->prepare($sql_cons);
        $stmt_cons->execute([$appointment_id, $patient_id, $doctor_id, $symptoms, $diagnosis, $advice]);
        
        // Get the ID of the consultation we just created
        $consultation_id = $pdo->lastInsertId();

        // 2. Insert into Prescriptions (Matches your schema: uses consultation_id)
        if (!empty($medicines)) {
            $sql_pres = "INSERT INTO Prescriptions (consultation_id, medicine_name, dosage, duration) VALUES (?, ?, ?, ?)";
            $stmt_pres = $pdo->prepare($sql_pres);

            foreach ($medicines as $index => $med_name) {
                if (!empty(trim($med_name))) { 
                    $stmt_pres->execute([
                        $consultation_id, 
                        $med_name, 
                        $dosages[$index] ?? '', 
                        $durations[$index] ?? ''
                    ]);
                }
            }
        }

        // 3. Update Appointment Status
        $stmt_update = $pdo->prepare("UPDATE Appointments SET status = 'Completed' WHERE appointment_id = ?");
        $stmt_update->execute([$appointment_id]);

        // 4. Create Billing Record (Status set to 'Pending' or 'Unpaid' to lock report)
        // Adjusting columns to match your 'Billing' table exactly
        $check_bill = $pdo->prepare("SELECT bill_id FROM Billing WHERE appointment_id = ?");
        $check_bill->execute([$appointment_id]);
        
        if (!$check_bill->fetch()) {
            $fee = 500.00;
            $stmt_bill = $pdo->prepare("INSERT INTO Billing (patient_id, appointment_id, amount, total_payable, status) 
                                       VALUES (?, ?, ?, ?, 'Pending')");
            $stmt_bill->execute([$patient_id, $appointment_id, $fee, $fee]);
        }

        $pdo->commit();
        header("Location: ../doctor/dashboard.php?status=success");
        exit;

    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        error_log("Consultation Error: " . $e->getMessage());
        die("Critical Database Error: " . $e->getMessage());
    }
}