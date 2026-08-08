<?php
session_start();
require '../config/db.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Donor') {
    die("Unauthorized Access");
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $donor_id = $_SESSION['user_id'];
    $request_id = $_POST['request_id'];
    $notification_id = isset($_POST['notification_id']) ? $_POST['notification_id'] : null;

    try {
        $pdo->beginTransaction();

        // 1. Prevent Double Response
        $check = $pdo->prepare("SELECT response_id FROM donor_responses WHERE request_id = ? AND donor_id = ?");
        $check->execute([$request_id, $donor_id]);
        if ($check->rowCount() > 0) {
            $pdo->rollBack();
            $_SESSION['success_message'] = "You already responded to this!";
            header("Location: ../donor_dashboard.php");
            exit();
        }

        // 2. Insert the Response
        $stmt1 = $pdo->prepare("INSERT INTO donor_responses (request_id, donor_id, status) VALUES (?, ?, 'Completed')");
        $stmt1->execute([$request_id, $donor_id]);

        // 3. FORCE STATUS CHANGE (This fixes your issue)
        // We set status to 'Resolved' so it disappears from the Map and Donor feeds immediately
        $stmt2 = $pdo->prepare("UPDATE emergency_requests SET status = 'Resolved' WHERE request_id = ?");
        $stmt2->execute([$request_id]);

        // 4. Clear all notifications for this specific request (No ghost alerts)
        $stmt3 = $pdo->prepare("UPDATE donor_notifications SET is_read = 1 WHERE request_id = ?");
        $stmt3->execute([$request_id]);

        $pdo->commit();

        $_SESSION['success_message'] = "Life Saved! The request is now closed and secured.";
        header("Location: ../donor_dashboard.php");
        exit();

    } catch (PDOException $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}