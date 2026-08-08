# Smart Healthcare Management System
The Smart Healthcare Management System is a digital healthcare solution designed to streamline patient, doctor, and hospital operations while introducing intelligent AI-based assistance. The system first establishes a strong foundation with Patient & Medical Record Management, ensuring secure, centralized storage of demographics, medical history, prescriptions, and test reports, along with Doctor & Appointment Management that allows smooth online booking, rescheduling, and reminders. It further enhances clinical workflow with Prescription & Investigation Management, where doctors can upload digital prescriptions and diagnostic results for easy patient access, and Billing & Insurance Automation, which simplifies invoice generation, claim processing, and payment tracking. On top of these core functions, the system integrates AI-driven features such as an AI-Powered Disease Prediction & Symptom Checker that analyzes patient symptoms and history to suggest possible conditions and AI-Based Smart Recommendations & Preventive Care, which guides patients to the right doctors, suggests necessary investigations, and provides preventive health reminders. Altogether, this project creates a comprehensive, intelligent, and patient-centric healthcare ecosystem that improves efficiency, reduces errors, and supports proactive health management.



===========================================================
SMART HEALTHCARE MANAGEMENT SYSTEM - INSTALLATION GUIDE
===========================================================

Project Description:
A PHP-based management system for doctors, admins, and patients.

-----------------------------------------------------------
1. PREREQUISITES
-----------------------------------------------------------
* XAMPP or WAMP installed.
* PHP 8.0 or higher.
* MySQL / MariaDB.

-----------------------------------------------------------
2. DATABASE SETUP (The most important step)
-----------------------------------------------------------
Before running the project, you must import the database:

1. Open XAMPP Control Panel and start 'Apache' and 'MySQL'.
2. Go to your browser and open: http://localhost/phpmyadmin/
3. Create a NEW database named: smart_hms
4. Click on the 'smart_hms' database on the left sidebar.
5. Click the 'Import' tab at the top.
6. Click 'Choose File' and select the .sql file located in 
   the /database folder of this project.
7. Scroll to the bottom and click 'Import'.

-----------------------------------------------------------
3. PROJECT CONFIGURATION
-----------------------------------------------------------
1. Move the entire project folder into: C:/xampp/htdocs/
2. Open 'config/db.php' in a text editor.
3. Ensure the database credentials match your local setup:
   - Host: localhost
   - Database: smart_hms
   - User: root
   - Password: (usually empty in XAMPP)

-----------------------------------------------------------
4. INITIAL SYSTEM SETUP
-----------------------------------------------------------
1. Open your browser and go to:
   http://localhost/Smart-Healthcare-Management-System/admin/admin_setup.php
   
2. Fill out the "Initial Admin Setup" form. This creates the
   Master Administrator account.
   
3. Once submitted, the setup page will lock, and you will be
   redirected to the Staff Login page.

-----------------------------------------------------------
5. FOLDER STRUCTURE
-----------------------------------------------------------
/actions        - Backend PHP processing scripts
/admin          - Admin dashboard and management pages
/doctor         - Doctor dashboard and prescription tools
/config         - Database connection settings
/database       - Contains the .sql export file
/assets         - CSS, JS, and Images

-----------------------------------------------------------
Support:
If you encounter "SQLSTATE[HY000] [1049] Unknown database", 
ensure your database name in phpMyAdmin matches the name 
in config/db.php exactly.
===========================================================