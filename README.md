# 📚 ExceLearn LMS (v2.1)

> **Official Learning Management System of Excelsior Polytechnic Academy**  
> *Cabitan, Mandaon, Masbate · Senior High School Department*

[![PHP Version](https://img.shields.io/badge/PHP-8.1%20%7C%208.2-blue.svg)](https://www.php.net/)
[![Database](https://img.shields.io/badge/Database-MariaDB%20%2F%20MySQL-orange.svg)](https://mariadb.org/)
[![License](https://img.shields.io/badge/License-Proprietary-red.svg)](license)
[![Manual](https://img.shields.io/badge/Documentation-ExceLearn%20Manual-green.svg)](MANUAL.md)

---

## 📖 Complete Documentation & Manual

For the in-depth system manual with **14 live screenshots**, **interactive Mermaid flowcharts**, **database ER diagrams**, and **page-by-page walkthroughs**, see:

👉 **[ExceLearn System Manual & Architecture Guide (MANUAL.md)](MANUAL.md)**

---

## 🌟 Overview & System Purpose

**ExceLearn** is a multi-tier academic Learning Management System engineered to digitalize and streamline Senior High School education at **Excelsior Polytechnic Academy**. 

The system maps DepEd K-to-12 academic tracks and strands (**STEM**, **ABM**, **HUMSS**, **TVL**, **GAS**) across Grade Levels 11 & 12, connecting administrators, faculty members, and students into a synchronized learning ecosystem:

- **🏛️ Administration:** Faculty onboarding and credential approvals, bulk student Excel enrollment, curriculum/subject mapping, sectioning, and institution-wide analytics.
- **👩‍🏫 Faculty:** Course creation, digital lesson modules with embedded PDF viewing, timed automated quizzes, rubric-graded performance tasks, WebRTC virtual classrooms, and quarterly grade calculation.
- **🎓 Students:** Unified learning dashboard, interactive course modules, real-time quiz runner with countdown timer, deliverable submission, progress badges, and downloadable completion certificates.
- **⚙️ Background Daemon:** Asynchronous background worker (`background_worker/background_worker.php`) handling real-time Web Push alerts via **OneSignal** and queued email dispatch via **PHPMailer**.

---

## 🛠️ Tech Stack & Key Libraries

- **Backend:** Native PHP 8.1+ / 8.2+
- **Database:** MariaDB 10.4+ / MySQL 8.0+ (`mysqli` driver)
- **Frontend:** Vanilla HTML5, CSS3 (Light/Dark themes), Vanilla JavaScript
- **Live Classroom:** WebRTC peer-to-peer audio, video, and screen sharing with in-database signaling
- **Composer Packages:**
  - `dompdf/dompdf`: Server-side PDF generation for student achievement certificates.
  - `phpoffice/phpspreadsheet`: High-speed Excel spreadsheet parsing for batch student enrollment and grade exports.
  - `phpmailer/phpmailer`: SMTP mail delivery for verification OTPs and account recovery.
- **Notifications:** OneSignal REST API integration for push alerts.

---

## ⚡ Quick Start: Running Locally

### 1. Prerequisites
- [XAMPP](https://www.apachefriends.org/) (PHP 8.1+ and MySQL/MariaDB) or equivalent local stack.

### 2. Configure the Database
1. Start **MySQL** in XAMPP.
2. Open your MySQL console or phpMyAdmin and run:
   ```sql
   CREATE DATABASE IF NOT EXISTS u342202101_excelearn;
   CREATE USER IF NOT EXISTS 'u342202101_excelearn'@'localhost' IDENTIFIED BY '6S+?/2^n|k?BMhhs';
   GRANT ALL PRIVILEGES ON u342202101_excelearn.* TO 'u342202101_excelearn'@'localhost';
   FLUSH PRIVILEGES;
   ```

### 3. Import the Database Dump
Because newer database dumps export with `utf8mb4_uca1400_ai_ci` collation, replace it before importing into MariaDB 10.4:

```powershell
# PowerShell: Make dump compatible and import
(Get-Content -Path .\u342202101_excelearn.sql -Raw) -replace 'utf8mb4_uca1400_ai_ci', 'utf8mb4_unicode_ci' | Set-Content -Path .\import_ready.sql -Encoding UTF8
cmd /c "C:\xampp\mysql\bin\mysql.exe -u root u342202101_excelearn < import_ready.sql"
Remove-Item -Path .\import_ready.sql -Force
```

### 4. Start the Application
Run PHP's built-in development server from the project directory:

```powershell
php -S localhost:8000 -t .
```

Open your browser at:  
👉 **`http://localhost:8000/index.php`**

---

## 🔑 Default Test Credentials

All active local accounts have been configured with the test password: **`Admin@123`**.

| User Tier | Tab on Login Page | Username / Identifier | Password | Dashboard URL |
| :--- | :--- | :--- | :--- | :--- |
| **Super Admin** | 👩‍🏫 Teacher | `excelsiorpolyacad@gmail.com` | `Admin@123` | [`admin/index.php`](admin/index.php) |
| **Teacher** | 👩‍🏫 Teacher | `junacvelarde@gmail.com` | `Admin@123` | [`teachers/index.php`](teachers/index.php) |
| **Teacher** | 👩‍🏫 Teacher | `jhamel.habla@deped.gov.ph` | `Admin@123` | [`teachers/index.php`](teachers/index.php) |
| **Student** | 🎓 Student | `STUD-123456` | `Admin@123` | [`students/index.php`](students/index.php) |
| **Student** | 🎓 Student | `STUD-2026-12345` | `Admin@123` | [`students/index.php`](students/index.php) |

---

## 📂 Project Structure Overview

```
excelearn/
├── README.md                      # Project overview, setup, and credentials (this file)
├── MANUAL.md                      # Comprehensive system manual, flowcharts, and screenshots
├── index.php                      # Dual-role login portal
├── register.php                   # Teacher self-registration
├── forgotpassword.php             # 3-step OTP password reset
├── connection.php                 # MySQLi database connector
├── logout.php                     # Session cleanup
├── u342202101_excelearn.sql       # Database schema & seeds
├── admin/                         # Administrator management portal
├── teachers/                      # Teacher course, quiz, grading & WebRTC portal
├── students/                      # Student modules, quizzes, and tasks portal
├── background_worker/             # Background push and notification daemon
├── screenshots/                   # Live UI documentation captures
├── assets/                        # Stylesheets, JS scripts, and school emblems
└── uploads/                       # Storage for PDFs, profile pictures, and submissions
```

---

## 📄 License & Copyright

Developed for **Excelsior Polytechnic Academy**  
Copyright © 2025–2026 Excelsior Polytechnic Academy. All rights reserved.
