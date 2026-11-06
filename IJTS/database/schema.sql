-- Internal Job Tracking System (IJTS) - database schema and demo data
-- Import this file in phpMyAdmin (Import tab) or run: mysql -u root -p < schema.sql

CREATE DATABASE IF NOT EXISTS ijts_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ijts_db;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications, audit_log, hiring_decisions, interviews, applications, vacancies, users, departments;
SET FOREIGN_KEY_CHECKS = 1;

CREATE TABLE departments (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(80) NOT NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE users (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(100) NOT NULL,
  email VARCHAR(120) NOT NULL UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  role ENUM('admin','hr','manager','employee') NOT NULL DEFAULT 'employee',
  department_id INT UNSIGNED NOT NULL,
  status ENUM('Active','Inactive') NOT NULL DEFAULT 'Active',
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (department_id) REFERENCES departments(id)
) ENGINE=InnoDB;

CREATE TABLE vacancies (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(150) NOT NULL,
  department_id INT UNSIGNED NOT NULL,
  requested_by INT UNSIGNED NOT NULL,
  positions TINYINT UNSIGNED NOT NULL DEFAULT 1,
  description TEXT NOT NULL,
  qualifications TEXT NOT NULL,
  justification TEXT NULL,
  closing_date DATE NOT NULL,
  status ENUM('Pending','Approved','Published','Returned','Rejected','Closed','Filled') NOT NULL DEFAULT 'Pending',
  hr_comment TEXT NULL,
  reviewed_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  published_at DATETIME NULL,
  FOREIGN KEY (department_id) REFERENCES departments(id),
  FOREIGN KEY (requested_by) REFERENCES users(id),
  FOREIGN KEY (reviewed_by) REFERENCES users(id),
  INDEX idx_vac_status (status)
) ENGINE=InnoDB;

CREATE TABLE applications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  vacancy_id INT UNSIGNED NOT NULL,
  user_id INT UNSIGNED NOT NULL,
  cover_note TEXT NULL,
  cv_file VARCHAR(100) NULL,
  status ENUM('Received','Shortlisted','Interview','Selected','Rejected','On Hold') NOT NULL DEFAULT 'Received',
  applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_vac_user (vacancy_id, user_id),
  FOREIGN KEY (vacancy_id) REFERENCES vacancies(id),
  FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE interviews (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNSIGNED NOT NULL,
  stage ENUM('Screening','First Interview','Final Interview') NOT NULL,
  interview_date DATE NOT NULL,
  panel VARCHAR(200) NULL,
  marks TINYINT UNSIGNED NULL,
  result ENUM('Pending','Passed','Failed') NOT NULL DEFAULT 'Pending',
  comments TEXT NULL,
  created_by INT UNSIGNED NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id),
  FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE hiring_decisions (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  application_id INT UNSIGNED NOT NULL,
  decision ENUM('Selected','Rejected','On Hold') NOT NULL,
  reason TEXT NOT NULL,
  decided_by INT UNSIGNED NOT NULL,
  decided_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (application_id) REFERENCES applications(id),
  FOREIGN KEY (decided_by) REFERENCES users(id)
) ENGINE=InnoDB;

CREATE TABLE notifications (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  message VARCHAR(255) NOT NULL,
  link VARCHAR(200) NULL,
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id),
  INDEX idx_note_user (user_id, is_read)
) ENGINE=InnoDB;

CREATE TABLE audit_log (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,
  action VARCHAR(60) NOT NULL,
  details VARCHAR(255) NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------- Demo data (all demo passwords: Pass@123) ----------------
INSERT INTO departments (id, name) VALUES
 (1,'ICT'),(2,'Accounts'),(3,'Marketing'),(4,'Engineering'),(5,'Human Resources');

INSERT INTO users (id, full_name, email, password_hash, role, department_id) VALUES
 (1,'Nimal Perera','admin@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','admin',5),
 (2,'Kavitha Selvam','hr@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','hr',5),
 (3,'Ravi Kumar','ict.head@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','manager',1),
 (4,'Priya Fernando','accounts.head@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','manager',2),
 (5,'Suresh Rajan','marketing.head@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','manager',3),
 (6,'Anjali Silva','engineering.head@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','manager',4),
 (7,'Arun Thiru','arun@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','employee',1),
 (8,'Meena Kanth','meena@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','employee',2),
 (9,'Dilan Jayasuriya','dilan@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','employee',3),
 (10,'Sanjay Mohan','sanjay@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','employee',4),
 (11,'Nisha Rao','nisha@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','employee',5),
 (12,'Kamal Fonseka','kamal@ijts.local','$2y$10$/MLl0HO3JNBfDRVcUcZ99.89MQzfHSD/Jk0GOzpO3/dLzSwgzAiIO','employee',1);

INSERT INTO vacancies (id, title, department_id, requested_by, positions, description, qualifications, justification, closing_date, status, hr_comment, reviewed_by, created_at, published_at) VALUES
 (1,'Senior Network Engineer',1,3,1,'Design, maintain and secure the company network, including branch connectivity and the server room.','BSc in IT or a related field. 3+ years of network administration. CCNA preferred.','Current network engineer is moving to the cloud project.','2026-11-15','Published',NULL,2,'2026-10-01 09:10:00','2026-10-02 10:00:00'),
 (2,'Assistant Accountant',2,4,2,'Prepare ledgers, reconcile accounts and support month-end closing.','Passed AAT or CIMA Level 2. Knowledge of accounting software.','Two vacancies after the audit workload increase.','2026-11-20','Published',NULL,2,'2026-10-03 11:30:00','2026-10-04 09:00:00'),
 (3,'Digital Marketing Executive',3,5,1,'Plan and run online campaigns and report on results.','Degree in marketing or media. 2 years of digital campaign experience.','Replacement for a resigned executive.','2026-09-30','Filled','Approved. Please share the shortlist by the 20th.',2,'2026-08-25 14:00:00','2026-09-01 09:30:00'),
 (4,'Site Supervisor',4,6,1,'Supervise site activities, safety and daily progress reports.','Diploma in civil engineering. 3 years on construction sites.','New project starting in January.','2026-12-10','Pending',NULL,NULL,'2026-10-08 15:20:00',NULL),
 (5,'Systems Analyst',1,3,1,'Analyse business needs and prepare system requirements.','BSc in IT or software engineering. 2 years as an analyst.','Needed for the new reporting project.','2026-12-01','Returned','Add the required qualifications and the reporting line, then resubmit.',2,'2026-10-06 10:05:00',NULL),
 (6,'HR Assistant',5,2,1,'Support recruitment, maintain employee records and schedule interviews.','Diploma in HR management. Good communication skills.','Support for the growing number of internal vacancies.','2026-11-30','Published',NULL,1,'2026-10-05 08:40:00','2026-10-05 12:00:00');

INSERT INTO applications (id, vacancy_id, user_id, cover_note, cv_file, status, applied_at) VALUES
 (1,1,7,'I have managed the branch network for two years and would like to take on the senior role.',NULL,'Interview','2026-10-03 10:15:00'),
 (2,1,12,'I hold CCNA and have supported the server room since 2024.',NULL,'Shortlisted','2026-10-04 16:40:00'),
 (3,2,11,'I want to move into finance and have completed AAT Level 1.',NULL,'Received','2026-10-06 09:05:00'),
 (4,3,10,'I have run social media campaigns for engineering product launches.',NULL,'Selected','2026-09-03 13:20:00'),
 (5,3,9,'I would like to grow from my current marketing role.',NULL,'Rejected','2026-09-04 11:00:00'),
 (6,6,8,'I have assisted HR with record keeping during audits.',NULL,'Received','2026-10-07 14:25:00');

INSERT INTO interviews (id, application_id, stage, interview_date, panel, marks, result, comments, created_by) VALUES
 (1,1,'Screening','2026-10-05','Kavitha Selvam',78,'Passed','Strong technical background.',2),
 (2,1,'First Interview','2026-10-09','Ravi Kumar, Kavitha Selvam',NULL,'Pending','Scheduled with the ICT head.',2),
 (3,4,'Screening','2026-09-08','Kavitha Selvam',82,'Passed','Good portfolio.',2),
 (4,4,'First Interview','2026-09-12','Suresh Rajan, Kavitha Selvam',85,'Passed','Clear campaign thinking.',2),
 (5,4,'Final Interview','2026-09-18','Suresh Rajan, Nimal Perera',88,'Passed','Recommended for appointment.',2),
 (6,5,'Screening','2026-09-08','Kavitha Selvam',70,'Passed','Meets the basic criteria.',2),
 (7,5,'First Interview','2026-09-12','Suresh Rajan, Kavitha Selvam',55,'Failed','Limited campaign experience.',2);

INSERT INTO hiring_decisions (id, application_id, decision, reason, decided_by, decided_at) VALUES
 (1,4,'Selected','Highest score across all three stages. Transfer from Engineering approved.',2,'2026-09-20 10:00:00'),
 (2,5,'Rejected','Did not pass the first interview.',2,'2026-09-14 15:30:00');

INSERT INTO notifications (user_id, message, link, is_read) VALUES
 (2,'New vacancy request: Site Supervisor (Engineering).','hr_requests.php',0),
 (3,'Your request "Systems Analyst" was returned for changes.','my_requests.php',0),
 (7,'Your application for Senior Network Engineer moved to Interview.','my_applications.php',0),
 (10,'Congratulations. You were selected for Digital Marketing Executive.','my_applications.php',1);

INSERT INTO audit_log (user_id, action, details) VALUES
 (3,'Vacancy request','Created request: Senior Network Engineer'),
 (2,'Vacancy published','Published: Senior Network Engineer'),
 (2,'Hiring decision','Selected Sanjay Mohan for Digital Marketing Executive');
