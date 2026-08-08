<?php
session_start();
require '../config/db.php'; 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    /** * role_check comes from:
     * 1. The <select> in staff_access.php (Doctor/Admin)
     * 2. A hidden input in patient_access.php (Patient)
     */
    $role_check = $_POST['role_check'] ?? ''; 

    // 1. Basic Validation
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Required: Both Login ID and Password.";
        redirectBack($role_check);
    }

    try {
        // 2. Look up user by username (Phone number for Doctor/Patient, Username for Admin)
        $stmt = $pdo->prepare("SELECT * FROM Users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // 3. Authentication & Security Check
        if ($user && password_verify($password, $user['password_hash'])) {
            
            // SECURITY: Ensure the user's role in DB matches the portal they are using
            if (!empty($role_check) && $user['role'] !== $role_check) {
                $_SESSION['error'] = "Access Denied: Incorrect portal for this account.";
                redirectBack($role_check);
            }

            // Success! Set session variables
            $_SESSION['user_id']  = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // 4. Smart Redirection based on actual DB role
            switch ($user['role']) {
                case 'Admin':
                    header('Location: ../admin/admin_dashboard.php');
                    break;
                case 'Doctor':
                    header('Location: ../doctor/dashboard.php');
                    break;
                case 'Patient':
                    header('Location: ../patient/dashboard.php');
                    break;
                default:
                    header('Location: ../index.php');
            }
            exit;

        } else {
            // Failure: User not found or password incorrect
            $_SESSION['error'] = "Invalid Login ID or Password.";
            redirectBack($role_check);
        }

    } catch (PDOException $e) {
        // Error handling (Database issues)
        $_SESSION['error'] = "System failure. Please try again later.";
        error_log("Login Error: " . $e->getMessage()); // Log error for dev
        redirectBack($role_check);
    }
}

/**
 * Intelligent Helper function to redirect back to the entry point
 */
function redirectBack($role) {
    if ($role === 'Patient') {
        header('Location: ../patient_access.php');
    } elseif ($role === 'Doctor' || $role === 'Admin') {
        header('Location: ../staff_access.php');
    } else {
        header('Location: ../index.php');
    }
    exit;
}
?>