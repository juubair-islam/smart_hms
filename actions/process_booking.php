<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $patient_id = $_SESSION['user_id'];
    $doctor_id  = $_POST['doctor_id'];
    $app_date   = $_POST['app_date'];
    $app_time   = $_POST['app_time'];
    $reason     = trim($_POST['reason']);
    
    // As per your schema: appointment_datetime
    $appointment_datetime = $app_date . ' ' . $app_time;

    try {
        // --- SMART DUPLICATE CHECK (Matching your schema) ---
        // Only blocks if an appointment is 'Scheduled' AND Bill is NOT 'Paid'
        $dup_stmt = $pdo->prepare("
            SELECT COUNT(*) 
            FROM Appointments a
            LEFT JOIN Billing b ON a.appointment_id = b.appointment_id
            WHERE a.patient_id = ? 
            AND a.doctor_id = ? 
            AND a.status = 'Scheduled'
            AND (b.status IS NULL OR b.status IN ('Unpaid', 'Pending'))
        ");
        $dup_stmt->execute([$patient_id, $doctor_id]);
        
        if ($dup_stmt->fetchColumn() > 0) {
            header("Location: ../patient/find_doctor.php?error=duplicate_booking");
            exit;
        }

        // 2. Validate Time Slot (doctor_id + appointment_datetime is UNIQUE in your schema)
        $check_stmt = $pdo->prepare("SELECT COUNT(*) FROM Appointments 
                                    WHERE doctor_id = ? AND appointment_datetime = ? 
                                    AND status != 'Cancelled'");
        $check_stmt->execute([$doctor_id, $appointment_datetime]);
        
        if ($check_stmt->fetchColumn() > 0) {
            header("Location: ../patient/find_doctor.php?error=slot_taken");
            exit;
        }

        // 3. Insert Appointment (Matches your Appointments table columns)
        $sql = "INSERT INTO Appointments (patient_id, appointment_date, doctor_id, appointment_datetime, reason, status) 
                VALUES (?, ?, ?, ?, ?, 'Scheduled')";
        
        $stmt = $pdo->prepare($sql);
        $result = $stmt->execute([
            $patient_id, 
            $app_date, // This is your added column from ALTER
            $doctor_id, 
            $appointment_datetime, 
            $reason
        ]);

        if ($result) {
            header("Location: ../patient/appointments.php?status=success");
        } else {
            header("Location: ../patient/find_doctor.php?error=failed");
        }

    } catch (PDOException $e) {
        error_log("Booking Error: " . $e->getMessage());
        header("Location: ../patient/find_doctor.php?error=db_error");
    }
}