<?php
session_start();
require '../config/db.php';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    
    $requester_id = $_SESSION['user_id'];
    $blood_group = $_POST['blood_group'];
    $urgency_level = $_POST['urgency_level'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];

    try {
        $pdo->beginTransaction();

        // 1. Create the Master Emergency Request
        $stmt_req = $pdo->prepare("INSERT INTO emergency_requests (requester_id, request_type, blood_group, urgency_level, latitude, longitude, status) VALUES (?, 'Blood', ?, ?, ?, ?, 'Active')");
        $stmt_req->execute([$requester_id, $blood_group, $urgency_level, $latitude, $longitude]);
        $request_id = $pdo->lastInsertId();

        // 2. Find EVERY Donor who has this exact Blood Group
        $stmt_donors = $pdo->prepare("SELECT patient_id FROM patients WHERE is_donor = 1 AND blood_group = ?");
        $stmt_donors->execute([$blood_group]);
        $matching_donors = $stmt_donors->fetchAll(PDO::FETCH_ASSOC);

        // 3. Loop through them and blast a notification directly to their dashboard!
        if (count($matching_donors) > 0) {
            $stmt_notify = $pdo->prepare("INSERT INTO donor_notifications (donor_id, request_id, message) VALUES (?, ?, ?)");
            $message = "URGENT: A patient nearby is in critical need of {$blood_group} blood. Please check the radar immediately!";
            
            foreach ($matching_donors as $donor) {
                $stmt_notify->execute([$donor['patient_id'], $request_id, $message]);
            }
        }

        $pdo->commit();
        
        // 4. Set a success message for the Patient
        $_SESSION['success'] = "Emergency request broadcasted! Notifying all " . $blood_group . " donors in the network.";
        
        // 5. Redirect back to the Patient Dashboard
        header("Location: ../patient/dashboard.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("System Error: " . $e->getMessage());
    }
} else {
    // If they aren't logged in, kick them back
    header("Location: ../patient_access.php");
    exit();
}
?>