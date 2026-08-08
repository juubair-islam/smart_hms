<?php
session_start();
require '../config/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $doctor_id = $_POST['doctor_id'];
    $expertise = $_POST['expertise'];
    $qualification = $_POST['qualification'];
    $contact = $_POST['contact'];
    $gender = $_POST['gender'];

    try {
        // Handle image if new one is uploaded
        if (isset($_FILES['doctor_image']) && $_FILES['doctor_image']['error'] == 0) {
            $ext = pathinfo($_FILES["doctor_image"]["name"], PATHINFO_EXTENSION);
            $filename = "doc_" . time() . "." . $ext;
            $target_path = "../assets/img/doctors/" . $filename;
            $db_path = "assets/img/doctors/" . $filename;

            if (move_uploaded_file($_FILES["doctor_image"]["tmp_name"], $target_path)) {
                // Update with new image
                $stmt = $pdo->prepare("UPDATE Doctors SET expertise=?, qualification=?, contact_number=?, gender=?, image_url=? WHERE doctor_id=?");
                $stmt->execute([$expertise, $qualification, $contact, $gender, $db_path, $doctor_id]);
            }
        } else {
            // Update without changing image
            $stmt = $pdo->prepare("UPDATE Doctors SET expertise=?, qualification=?, contact_number=?, gender=? WHERE doctor_id=?");
            $stmt->execute([$expertise, $qualification, $contact, $gender, $doctor_id]);
        }

        $_SESSION['success'] = "Doctor profile updated successfully!";
        header('Location: ../admin/manage_doctors.php');
        exit;
    } catch (PDOException $e) {
        die("Update Error: " . $e->getMessage());
    }
}