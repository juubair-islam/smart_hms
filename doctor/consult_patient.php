<?php
session_start();
require '../config/db.php';

// 1. Security Check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Doctor') {
    header('Location: ../staff_access.php');
    exit;
}

$appointment_id = $_GET['id'] ?? null;
if (!$appointment_id) {
    header('Location: dashboard.php');
    exit;
}

try {
    // 2. Fetch Appointment & Patient Details
    $stmt = $pdo->prepare("SELECT a.*, p.* FROM Appointments a 
                            JOIN Patients p ON a.patient_id = p.patient_id 
                            WHERE a.appointment_id = ?");
    $stmt->execute([$appointment_id]);
    $data = $stmt->fetch();

    if (!$data) { die("Appointment not found."); }

    // 3. Prevent editing a completed consultation
    if ($data['status'] === 'Completed') {
        $_SESSION['error'] = "This consultation has already been finalized.";
        header("Location: dashboard.php");
        exit;
    }

    // 4. NEW: Fetch Previous Medical History for the Modal
    $stmt_hist = $pdo->prepare("SELECT c.*, a.appointment_date 
                                FROM Consultations c 
                                JOIN Appointments a ON c.appointment_id = a.appointment_id 
                                WHERE c.patient_id = ? ORDER BY a.appointment_date DESC LIMIT 5");
    $stmt_hist->execute([$data['patient_id']]);
    $history = $stmt_hist->fetchAll();

    $age = (new DateTime())->diff(new DateTime($data['date_of_birth']))->y;

} catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Consultation | Smart HMS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root { --med-blue: #0077b6; --soft-bg: #f8fafc; }
        body { background: var(--soft-bg); font-family: 'Inter', sans-serif; }
        
        .consult-header { background: white; border-bottom: 1px solid #e2e8f0; padding: 15px 40px; z-index: 1000; }
        .patient-sidebar { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; height: fit-content; position: sticky; top: 100px; }
        .clinical-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 30px; }
        
        .vitals-badge { background: #f0f9ff; color: #0369a1; padding: 10px 15px; border-radius: 12px; font-weight: 600; border: 1px solid #bae6fd; }
        .section-label { font-size: 0.8rem; font-weight: 800; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; margin-bottom: 15px; display: block; }
        
        textarea.form-control { border-radius: 12px; border: 1px solid #e2e8f0; padding: 15px; background: #fcfcfc; resize: none; }
        textarea.form-control:focus { border-color: var(--med-blue); box-shadow: none; background: white; }
        
        .medicine-row { transition: all 0.3s ease; }
        .modal-content { border-radius: 20px; border: none; }
    </style>
</head>
<body>

<div class="consult-header d-flex justify-content-between align-items-center sticky-top shadow-sm">
    <div class="d-flex align-items-center">
        <a href="dashboard.php" class="btn btn-light rounded-circle me-3"><i class="bi bi-arrow-left"></i></a>
        <div>
            <h5 class="fw-bold mb-0">Active Consultation</h5>
            <small class="text-muted"><i class="bi bi-dot text-success"></i> Patient: #P-<?= $data['patient_id'] ?></small>
        </div>
    </div>
    <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary px-4 rounded-pill fw-bold">Save Draft</button>
        <button type="button" onclick="confirmSubmission()" class="btn btn-primary px-4 rounded-pill fw-bold shadow-sm">
            <i class="bi bi-check2-circle me-2"></i>Finalize & Submit
        </button>
    </div>
</div>

<div class="container-fluid py-4 px-md-5">
    <div class="row g-4">
        <div class="col-lg-3">
            <div class="patient-sidebar shadow-sm">
                <div class="text-center mb-4">
                    <div class="mx-auto bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center mb-3" style="width: 70px; height: 70px; font-size: 1.5rem; font-weight: 800;">
                        <?= substr($data['full_name'], 0, 1) ?>
                    </div>
                    <h5 class="fw-bold mb-1"><?= htmlspecialchars($data['full_name']) ?></h5>
                    <span class="badge bg-light text-dark border rounded-pill px-3"><?= $age ?> Years • <?= $data['gender'] ?></span>
                </div>
                
                <hr class="opacity-10">
                
                <span class="section-label">Medical Details</span>
                <div class="vitals-badge mb-3 d-flex justify-content-between">
                    <span>Blood Group</span>
                    <span><?= htmlspecialchars($data['blood_group'] ?? 'N/A') ?></span>
                </div>
                
                <p class="small text-muted mb-4"><i class="bi bi-info-circle me-2"></i> Check past medical history before finalizing diagnosis.</p>
                
                <button type="button" class="btn btn-outline-primary btn-sm w-100 mb-2 rounded-3 text-start" data-bs-toggle="modal" data-bs-target="#historyModal">
                    <i class="bi bi-clock-history me-2"></i> Past Visits (<?= count($history) ?>)
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm w-100 rounded-3 text-start" onclick="showLabAlert()">
                    <i class="bi bi-file-earmark-medical me-2"></i> Lab Reports
                </button>
            </div>
        </div>

        <div class="col-lg-9">
            <form id="consultationForm" action="../actions/save_consultation.php" method="POST">
                <input type="hidden" name="appointment_id" value="<?= $appointment_id ?>">
                <input type="hidden" name="patient_id" value="<?= $data['patient_id'] ?>">

                <div class="clinical-card shadow-sm mb-4 border-top border-primary border-4">
                    <h5 class="fw-bold mb-4 text-primary d-flex align-items-center">
                        <i class="bi bi-clipboard2-pulse me-2"></i> Clinical Assessment
                    </h5>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="section-label">Chief Complaints</label>
                            <textarea name="symptoms" class="form-control" rows="5" placeholder="List patient symptoms..." required></textarea>
                        </div>
                        <div class="col-md-6">
                            <label class="section-label">Diagnosis</label>
                            <textarea name="diagnosis" class="form-control" rows="5" placeholder="Enter clinical diagnosis..." required></textarea>
                        </div>
                    </div>
                </div>

                <div class="clinical-card shadow-sm">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0 text-primary d-flex align-items-center">
                            <i class="bi bi-capsule me-2"></i> Prescription
                        </h5>
                        <button type="button" class="btn btn-sm btn-primary rounded-pill px-3" onclick="addMedicationRow()">
                            <i class="bi bi-plus-lg me-1"></i> Add Medicine
                        </button>
                    </div>

                    <div id="medicationList">
                        <div class="row g-2 mb-3 align-items-end medicine-row">
                            <div class="col-md-5">
                                <label class="small fw-bold text-muted">Medicine & Strength</label>
                                <input type="text" name="medicine[]" class="form-control" placeholder="e.g. Amoxicillin 500mg">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Dosage</label>
                                <input type="text" name="dosage[]" class="form-control" placeholder="1-0-1">
                            </div>
                            <div class="col-md-3">
                                <label class="small fw-bold text-muted">Duration</label>
                                <input type="text" name="duration[]" class="form-control" placeholder="7 Days">
                            </div>
                            <div class="col-md-1">
                                <button type="button" class="btn btn-outline-danger border-0" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <label class="section-label">Advice & Follow-up</label>
                        <textarea name="advice" class="form-control" rows="2" placeholder="Dietary advice or return visit date..."></textarea>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="historyModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content shadow-lg">
            <div class="modal-header border-0 pb-0">
                <h5 class="fw-bold">Past Records: <?= htmlspecialchars($data['full_name']) ?></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4">
                <?php if(empty($history)): ?>
                    <div class="text-center py-4">
                        <i class="bi bi-folder2-open display-4 text-muted"></i>
                        <p class="mt-2 text-muted">No previous visits found for this patient.</p>
                    </div>
                <?php else: ?>
                    <div class="timeline shadow-none">
                        <?php foreach($history as $h): ?>
                            <div class="p-3 border rounded-4 mb-3 bg-light">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary rounded-pill">Visit: <?= date('d M, Y', strtotime($h['appointment_date'])) ?></span>
                                </div>
                                <div class="mb-1"><strong>Symptoms:</strong> <?= htmlspecialchars($h['symptoms']) ?></div>
                                <div><strong>Diagnosis:</strong> <span class="text-primary fw-bold"><?= htmlspecialchars($h['diagnosis']) ?></span></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function addMedicationRow() {
        const row = `
        <div class="row g-2 mb-3 align-items-end medicine-row">
            <div class="col-md-5">
                <input type="text" name="medicine[]" class="form-control" placeholder="Medicine Name">
            </div>
            <div class="col-md-3">
                <input type="text" name="dosage[]" class="form-control" placeholder="Dosage">
            </div>
            <div class="col-md-3">
                <input type="text" name="duration[]" class="form-control" placeholder="Duration">
            </div>
            <div class="col-md-1">
                <button type="button" class="btn btn-outline-danger border-0" onclick="this.parentElement.parentElement.remove()"><i class="bi bi-trash"></i></button>
            </div>
        </div>`;
        document.getElementById('medicationList').insertAdjacentHTML('beforeend', row);
    }

    // Modern In-Page Confirmation
    function confirmSubmission() {
        Swal.fire({
            title: 'Finalize Consultation?',
            text: "This will save the medical report and finalize the appointment. You cannot edit it later.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0077b6',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, Submit Now',
            cancelButtonText: 'Review Again',
            customClass: {
                popup: 'rounded-4 shadow'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('consultationForm').submit();
            }
        });
    }

    // In-Page Lab Report Alert
    function showLabAlert() {
        Swal.fire({
            title: 'No Digital Reports',
            text: 'No laboratory reports are linked to this patient ID yet.',
            icon: 'info',
            confirmButtonColor: '#0077b6',
            customClass: {
                popup: 'rounded-4 shadow'
            }
        });
    }
</script>

</body>
</html>