<?php
session_start();
require '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $full_name = $_POST['name'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = 'Admin';

    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Username and password are required.";
        header('Location: ../admin/admin_register.php');
        exit;
    }
    
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    try {
        $pdo->beginTransaction();

        // 1. Insert into USERS table
        $stmt_user = $pdo->prepare("INSERT INTO Users (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt_user->execute([$username, $hashed_password, $role]);
        $user_id = $pdo->lastInsertId();

        // 2. Insert into ADMINS table
        $stmt_admin = $pdo->prepare("INSERT INTO Admins (admin_id) VALUES (?)");
        $stmt_admin->execute([$user_id]);
        
        $pdo->commit();

        $_SESSION['success'] = "Admin account created successfully! You can now log in.";
        header('Location: ../index.php'); // Redirect to main login page
        exit;

    } catch (PDOException $e) {
        $pdo->rollBack();
        if ($e->getCode() == '23000') {
             $_SESSION['error'] = "Username already taken.";
        } else {
             $_SESSION['error'] = "Admin registration failed: A database error occurred.";
        }
        header('Location: ../admin/admin_register.php');
        exit;
    }
}
?>