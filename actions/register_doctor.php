<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
require '../config/db.php';

// 1. Security Check: Only Admins can access this action
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Admin') {
    header('Location: ../staff_access.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // 2. Capture and Sanitize Form Data
    $full_name     = trim($_POST['full_name']);
    $expertise     = $_POST['expertise'];
    $qualification = trim($_POST['qualification']);
    $contact       = trim($_POST['contact']);
    $gender        = $_POST['gender'];
    $username      = trim($_POST['username']);
    
    // Hash password for the Users table
    $password_hash = password_hash($_POST['password'], PASSWORD_DEFAULT);

    try {
        $pdo->beginTransaction();

        // 3. Handle Image Upload
        $db_image_path = 'assets/img/default_doc.jpg'; // Default path
        
        if (isset($_FILES['doctor_image']) && $_FILES['doctor_image']['error'] == 0) {
            $target_dir = "../assets/img/doctors/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            $ext = pathinfo($_FILES["doctor_image"]["name"], PATHINFO_EXTENSION);
            $filename = "doc_" . time() . "." . $ext;
            $target_file = $target_dir . $filename;

            if (move_uploaded_file($_FILES["doctor_image"]["tmp_name"], $target_file)) {
                // Save relative path for database display on the frontend
                $db_image_path = "assets/img/doctors/" . $filename;
            }
        }

        // 4. Insert into Users Table (For Authentication)
        // Note: Column changed to 'password_hash' to align with Login script
        $stmt1 = $pdo->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, 'Doctor')");
        $stmt1->execute([$username, $password_hash]);
        $user_id = $pdo->lastInsertId();

        // 5. Insert into Doctors Table (For Professional Profile)
        // IMPORTANT: We use $user_id as the doctor_id to link the tables
        $stmt2 = $pdo->prepare("INSERT INTO Doctors (doctor_id, full_name, expertise, qualification, contact_number, gender, image_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
        
        $stmt2->execute([
            $user_id, 
            $full_name, 
            $expertise, 
            $qualification, 
            $contact, 
            $gender, 
            $db_image_path
        ]);

        $pdo->commit();
        
        $_SESSION['success'] = "Doctor $full_name registered successfully!";
        header('Location: ../admin/manage_doctors.php');
        exit;

    } catch (Exception $e) {
        $pdo->rollBack();
        // Log the error and tell the admin what went wrong
        $_SESSION['error'] = "Registration failed: " . $e->getMessage();
        header('Location: ../admin/register_doctor_page.php'); 
        exit;
    }
}