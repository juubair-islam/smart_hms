<?php
session_start();
require '../config/db.php';

// Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Donor') {
    die("Unauthorized Access");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $donor_id = $_SESSION['user_id'];
    $full_name = trim($_POST['full_name']);
    
    // Enforce BD Phone Number format
    $raw_phone = trim($_POST['contact_phone']);
    $contact_phone = '+880' . $raw_phone;

    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    if (!preg_match('/^1[3-9][0-9]{8}$/', $raw_phone)) {
        die("Invalid BD Mobile Number format. Please enter 10 digits without the leading zero.");
    }

    try {
        // Notice: blood_group is INTENTIONALLY left out of this query to protect it from being changed!
        $stmt = $pdo->prepare("UPDATE patients SET full_name = ?, contact_phone = ?, latitude = ?, longitude = ? WHERE patient_id = ?");
        $stmt->execute([$full_name, $contact_phone, $latitude, $longitude, $donor_id]);

        $_SESSION['success_message'] = "Profile and Location updated successfully!";
        header("Location: ../donor_dashboard.php");
        exit();

    } catch (PDOException $e) {
        die("Error updating profile: " . $e->getMessage());
    }
} else {
    header("Location: ../donor_dashboard.php");
    exit();
}
?>