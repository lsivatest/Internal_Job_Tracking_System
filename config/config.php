<?php
// ---- Database settings (edit these for your server) ----
define('DB_HOST', 'localhost');
define('DB_NAME', 'ijts_db');
define('DB_USER', 'root');   // XAMPP default
define('DB_PASS', '');       // XAMPP default is an empty password

// ---- Application settings ----
define('APP_NAME', 'IJTS');
define('APP_FULL_NAME', 'Internal Job Tracking System');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_UPLOAD', 2 * 1024 * 1024); // 2 MB CV limit

date_default_timezone_set('Asia/Colombo');
