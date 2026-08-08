<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Patient') {
    header('Location: ../index.php');
    exit;
}
require '../config/db.php';
$patient_id = $_SESSION['user_id'];

// Unified Header Data
$stmt = $pdo->prepare("SELECT * FROM Patients WHERE patient_id = ?");
$stmt->execute([$patient_id]);
$patient = $stmt->fetch();
$dob = new DateTime($patient['date_of_birth']);
$calculated_age = (new DateTime())->diff($dob)->y;

// Knowledge Base
$medical_data = [
    ["disease" => "Common Cold", "symptoms" => ["cough", "runny nose", "sore throat", "sneezing"], "desc" => "A viral infection of your nose and throat."],
    ["disease" => "Influenza (Flu)", "symptoms" => ["fever", "body aches", "fatigue", "headache", "cough"], "desc" => "A common viral infection that can be severe."],
    ["disease" => "COVID-19", "symptoms" => ["fever", "loss of taste", "loss of smell", "shortness of breath", "fatigue"], "desc" => "An infectious disease caused by the SARS-CoV-2 virus."],
    ["disease" => "Migraine", "symptoms" => ["headache", "dizziness", "nausea", "light sensitivity"], "desc" => "A headache of varying intensity, often accompanied by nausea."],
    ["disease" => "Gastroenteritis", "symptoms" => ["nausea", "stomach pain", "diarrhea", "vomiting"], "desc" => "Intestinal infection marked by diarrhea and cramps."],
    ["disease" => "Bronchitis", "symptoms" => ["chest pain", "shortness of breath", "cough", "fatigue"], "desc" => "Inflammation of the lining of your bronchial tubes."]
];

$all_symptoms = ["fever", "cough", "shortness of breath", "fatigue", "body aches", "loss of taste", "loss of smell", "sore throat", "runny nose", "nausea", "chest pain", "dizziness", "headache", "stomach pain", "vomiting", "sneezing"];
sort($all_symptoms);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>AI Disease Prediction | Smart Health Care</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root { --primary: #0077b6; --bg: #f4f7fe; --sidebar: #ffffff; }
        body { background-color: var(--bg); font-family: 'Segoe UI', sans-serif; }
        
        /* Unified Sidebar */
        .sidebar { background: var(--sidebar); min-height: 100vh; border-right: 1px solid #e2e8f0; position: fixed; width: 280px; z-index: 1000; }
        .main-content { margin-left: 280px; padding: 40px; }
        .brand-box { padding: 30px 25px; line-height: 1.2; }
        .brand-text-top { font-size: 1.4rem; font-weight: 800; color: var(--primary); display: block; }
        .brand-text-bottom { font-size: 0.9rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 1px; }

        .nav-link { color: #64748b; padding: 14px 20px; border-radius: 12px; margin: 4px 20px; display: flex; align-items: center; transition: 0.3s; text-decoration: none; }
        .nav-link:hover, .nav-link.active { background: #eff6ff; color: var(--primary); font-weight: 600; }
        
        /* Unified Profile Header */
        .profile-header-card { background: white; padding: 25px; border-radius: 20px; border: 1px solid #e2e8f0; box-shadow: 0 10px 15px -3px rgba(0,0,0,0.05); margin-bottom: 30px; }
        
        /* Unified Record Design */
        .record-entry { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 25px; margin-bottom: 25px; transition: 0.3s; }
        .date-badge { width: 70px; height: 70px; background: #f0f9ff; border-radius: 15px; display: flex; align-items: center; justify-content: center; border: 1px solid #e0f2fe; }
        
        .prescription-box { background: #f0f9ff; border-left: 4px solid var(--primary); padding: 20px; border-radius: 12px; margin-top: 15px; }
        .ai-score-pill { font-size: 0.75rem; font-weight: 700; background: #fffbeb; color: #92400e; border: 1px solid #fde68a; padding: 4px 12px; border-radius: 20px; }

        /* Custom Input Styling */
        .symptom-tag { cursor: pointer; background: #f1f5f9; padding: 8px 18px; border-radius: 50px; margin: 5px; display: inline-block; transition: 0.2s; font-size: 0.9rem; border: 1px solid #e2e8f0; color: #475569; }
        .symptom-tag.active { background: var(--primary); color: white; border-color: var(--primary); font-weight: 600; }
        .input-area { border-radius: 15px; border: 1px solid #e2e8f0; padding: 15px; font-size: 1rem; transition: 0.3s; }
        .input-area:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0,119,182,0.1); }
        
        /* Loader */
        .brain-loader { width: 50px; height: 50px; border: 4px solid #f3f3f3; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="brand-box">
        <span class="brand-text-top">Smart Health Care</span>
        <span class="brand-text-bottom">Management System</span>
    </div>
    <nav class="nav flex-column mt-4">
        <a class="nav-link" href="dashboard.php"><i class="bi bi-house-heart me-3"></i> Overview</a>
        <a class="nav-link" href="appointments.php"><i class="bi bi-calendar-event me-3"></i> My Bookings</a>
        <a class="nav-link" href="medical_records.php"><i class="bi bi-file-earmark-medical me-3"></i> Health Records</a>
        <a class="nav-link active" href="ai_symptom_checker.php"><i class="bi bi-cpu me-3"></i> AI Disease Checker</a>
        <a class="nav-link" href="billing.php"><i class="bi bi-wallet2 me-3"></i> Billing</a>
        <div class="mt-auto px-4 pt-5"><hr></div>
        <a class="nav-link text-danger mb-4" href="../actions/logout.php"><i class="bi bi-power me-3"></i> Sign Out</a>
    </nav>
</div>

<div class="main-content">
    <div class="profile-header-card d-flex justify-content-between align-items-center">
        <div>
            <h3 class="fw-bold text-dark mb-1"><?= strtoupper(htmlspecialchars($patient['full_name'])) ?></h3>
            <div class="d-flex gap-3 align-items-center text-muted small">
                <span><i class="bi bi-person"></i> <?= $calculated_age ?> Years • <?= $patient['gender'] ?></span>
                <span>|</span>
                <span><i class="bi bi-telephone"></i> <?= htmlspecialchars($patient['contact_phone']) ?></span>
            </div>
        </div>
        <div class="badge bg-primary-subtle text-primary rounded-pill px-3 py-2">
            <i class="bi bi-stars me-1"></i> AI Module Active
        </div>
    </div>

    <h4 class="fw-bold mb-4">AI Symptom Analysis</h4>

    <div class="record-entry" id="ui-input">
        <div class="row">
            <div class="col-md-auto text-center">
                <div class="date-badge">
                    <i class="bi bi-search-heart text-primary fs-3"></i>
                </div>
            </div>
            <div class="col">
                <h5 class="fw-bold mb-3">Describe Symptoms</h5>
                <textarea id="userSpeech" class="form-control input-area mb-4" rows="3" placeholder="Tell the AI how you feel (e.g., 'I have a headache and I feel dizzy')"></textarea>
                
                <h6 class="fw-bold small text-muted text-uppercase mb-3">Quick Select Symptoms</h6>
                <div class="mb-4">
                    <?php foreach($all_symptoms as $s): ?>
                        <span class="symptom-tag" onclick="this.classList.toggle('active')"><?= $s ?></span>
                    <?php endforeach; ?>
                </div>
                
                <button onclick="analyzeText()" class="btn btn-primary rounded-pill px-5 fw-bold py-2">
                    <i class="bi bi-cpu me-2"></i> Start Analysis
                </button>
            </div>
        </div>
    </div>

    <div id="ui-loader" class="record-entry text-center py-5" style="display:none;">
        <div class="brain-loader mx-auto mb-3"></div>
        <h5 class="fw-bold">Analyzing Symptoms...</h5>
        <p class="text-muted small">The AI is cross-referencing your inputs with our medical knowledge base.</p>
    </div>

    <div id="ui-result" class="record-entry" style="display:none;">
        <div class="row">
            <div class="col-md-auto text-center">
                <div class="date-badge" style="background: #ecfdf5; border-color: #a7f3d0;">
                    <i class="bi bi-check2-circle text-success fs-3"></i>
                </div>
            </div>
            <div class="col">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="fw-bold small text-muted text-uppercase mb-1">AI Prediction Result</h6>
                        <h2 class="fw-bold text-dark" id="out-disease">...</h2>
                    </div>
                    <span id="out-prob" class="ai-score-pill">...</span>
                </div>

                <div class="prescription-box">
                    <h6 class="fw-bold small text-primary text-uppercase mb-2">Condition Description</h6>
                    <p id="out-desc" class="mb-0 text-dark"></p>
                </div>

                <div class="mt-4 row g-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light text-center">
                            <h4 class="fw-bold text-dark mb-0" id="out-match">0</h4>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Symptom Matches</small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 border rounded-4 bg-light text-center">
                            <h4 class="fw-bold text-warning mb-0">Elevated</h4>
                            <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Confidence Tier</small>
                        </div>
                    </div>
                </div>

                <div class="mt-4 pt-3 border-top d-flex gap-2">
                    <a href="appointments.php" class="btn btn-primary rounded-pill px-4 btn-sm">Book Doctor Now</a>
                    <button onclick="location.reload()" class="btn btn-outline-secondary rounded-pill px-4 btn-sm">Reset Analysis</button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const knowledgeBase = <?= json_encode($medical_data) ?>;
const symptomsList = <?= json_encode($all_symptoms) ?>;

function analyzeText() {
    const text = document.getElementById('userSpeech').value.toLowerCase();
    const activeTags = Array.from(document.querySelectorAll('.symptom-tag.active')).map(t => t.innerText.toLowerCase());
    let detectedInText = symptomsList.filter(s => text.includes(s));
    let finalSymptoms = [...new Set([...activeTags, ...detectedInText])];

    if (finalSymptoms.length === 0 && text.trim().length < 5) {
        alert("Please describe your symptoms or select tags.");
        return;
    }

    document.getElementById('ui-input').style.display = 'none';
    document.getElementById('ui-loader').style.display = 'block';

    setTimeout(() => {
        let topResult = { disease: "Inconclusive Viral Syndrome", score: 30, count: 0, desc: "AI requires more specific symptoms. Please consult a doctor for a professional diagnosis." };

        knowledgeBase.forEach(kb => {
            let matches = kb.symptoms.filter(s => finalSymptoms.includes(s));
            let score = (matches.length / kb.symptoms.length) * 100;

            if (score > topResult.score) {
                topResult = { 
                    disease: kb.disease, 
                    score: Math.round(score), 
                    count: matches.length,
                    desc: kb.desc 
                };
            }
        });

        document.getElementById('ui-loader').style.display = 'none';
        document.getElementById('ui-result').style.display = 'block';
        
        document.getElementById('out-disease').innerText = topResult.disease;
        document.getElementById('out-desc').innerText = topResult.desc;
        document.getElementById('out-prob').innerText = "Match Score: " + topResult.score + "%";
        document.getElementById('out-match').innerText = topResult.count;
    }, 1500);
}
</script>
</body>
</html>