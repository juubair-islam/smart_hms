<?php
session_start();
require '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $role = $_POST['role'] ?? '';

    // --- 1. Security Check (Only Patient Registration Allowed Here) ---
    if ($role !== 'Patient') {
        $_SESSION['error'] = "Access denied. Only patient registration is allowed from this page.";
        header('Location: ../index.php'); // Redirects to index or the dedicated admin/doctor login page
        exit;
    }

    // --- 2. Gather Patient-Specific Data ---
    $full_name = $_POST['name'] ?? '';
    $contact_phone = $_POST['contact_phone'] ?? ''; // This is the new username
    $password = $_POST['password'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? null;
    $gender = $_POST['gender'] ?? '';
    $email = $_POST['email'] ?? null;
    $ec_name = $_POST['ec_name'] ?? '';
    $ec_number = $_POST['ec_number'] ?? '';
    $ec_relation = $_POST['ec_relation'] ?? '';

    // Simple Input Validation
    if (empty($contact_phone) || empty($password) || empty($full_name)) {
        $_SESSION['error'] = "Missing required personal or contact information.";
        header('Location: ../patient_access.php');
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();

        // --- 3. Insert into USERS table (using contact_phone as username) ---
        $stmt_user = $pdo->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt_user->execute([$contact_phone, $hashed_password, $role]);
        $user_id = $pdo->lastInsertId();

        // --- 4. Insert into PATIENTS table (using new fields) ---
        $stmt_patient = $pdo->prepare("INSERT INTO Patients 
            (patient_id, full_name, date_of_birth, gender, contact_phone, email, 
             emergency_contact_name, emergency_contact_number, emergency_contact_relation) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $stmt_patient->execute([
            $user_id, 
            $full_name, 
            $date_of_birth, 
            $gender, 
            $contact_phone, 
            $email, 
            $ec_name, 
            $ec_number, 
            $ec_relation
        ]);
        
        $pdo->commit();
        $_SESSION['success'] = "Registration successful! You can now log in with your contact number and password.";
        header('Location: ../patient_access.php');
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == '23000') {
             $_SESSION['error'] = "Error: Contact Number is already registered. Please try logging in.";
        } else {
             $_SESSION['error'] = "Registration failed: A database error occurred.";
             // Optional: echo $e->getMessage(); for debugging purposes
        }
        header('Location: ../patient_access.php');
        exit;
    }
}
?>