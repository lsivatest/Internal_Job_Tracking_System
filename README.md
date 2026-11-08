# Internal Job Tracking System (IJTS)

A PHP + MySQL web application for a company with five departments (ICT, Accounts, Marketing, Engineering, Human Resources). Departments send vacancy requests, **Human Resources reviews and publishes every vacancy**, employees apply, and HR tracks **vacancy postings, applications, interview stages and hiring decisions** in one place.

## Requirements
PHP 7.4 or later (8.x recommended) with PDO MySQL and mbstring, MySQL 5.7+ or MariaDB 10.3+, Apache. XAMPP, WAMP or Laragon includes all of these.

## Installation (XAMPP on Windows)
1. Copy the `ijts` folder into `C:\xampp\htdocs\`.
2. Start **Apache** and **MySQL** in the XAMPP control panel.
3. Open `http://localhost/phpmyadmin`, click **Import**, choose `database/schema.sql` and click **Import**. This creates the `ijts_db` database with demo data.
4. If your MySQL user or password is not the XAMPP default (`root` and empty), edit `config/config.php`.
5. Open `http://localhost/ijts/` and sign in.

## Demo accounts (password for all: `Pass@123`)
| Role | Email |
|---|---|
| HR manager (administrator) | admin@ijts.local |
| HR officer | hr@ijts.local |
| Department head (ICT) | ict.head@ijts.local |
| Department head (Accounts) | accounts.head@ijts.local |
| Department head (Marketing) | marketing.head@ijts.local |
| Department head (Engineering) | engineering.head@ijts.local |
| Employee | arun@ijts.local, meena@ijts.local, kamal@ijts.local ... |

**Change these passwords (Users page) before real use.**

## What each role can do
- **Department head:** send a vacancy request, edit and resubmit a returned request, see the status and the selected employee for each request.
- **HR officer / HR manager:** approve, return, reject, publish and close vacancies; view all applications; shortlist; record interview stages (screening, first, final) with marks and comments; record the hiring decision; view hiring records ("who got which job"), reports and CSV export.
- **HR manager (administrator) also:** manage users and read the audit log.
- **Employee:** browse open vacancies, apply with a note and optional CV, follow the progress tracker.

## Workflow
Request (Pending) -> HR review (Approved / Returned / Rejected) -> Published -> Applications (Received -> Shortlisted -> Interview) -> Decision (Selected / Rejected / On hold) -> Vacancy Filled when all positions are selected.

## Folder structure
```
ijts/
  index.php, login.php, logout.php, dashboard.php
  vacancies.php, vacancy_view.php, request_new.php, my_requests.php, my_applications.php
  hr_requests.php, applications.php, application_view.php, hires.php, reports.php
  users.php, audit.php, notifications.php, download.php
  config/config.php        database settings
  includes/                db.php, functions.php, header.php, footer.php
  assets/css, assets/js    stylesheet and small script
  database/schema.sql      tables + demo data
  uploads/                 CV files (blocked from direct access)
  design/                  DESIGN_GUIDE.md and style-guide.html (for Figma)
```

## Security features
Prepared statements (PDO) everywhere, password hashing (`password_hash`), session ID regeneration at login, CSRF tokens on every form, output escaping, role checks on every page, CV uploads limited to PDF/DOC/DOCX up to 2 MB with random file names and served only through `download.php`, and an audit log.

## Notes
- Fonts load from Google Fonts; without internet the system fonts are used instead.
- For a live server, use HTTPS, create a MySQL user with limited rights instead of `root`, and turn off PHP error display.
- Not included (out of project scope): email sending, payroll, external applicants.
