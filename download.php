<?php
require_once 'includes/functions.php';
require_login();
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$a = q_one("SELECT a.cv_file, a.user_id, u.full_name FROM applications a JOIN users u ON u.id = a.user_id WHERE a.id = ?", [$id]);
if (!$a || !$a['cv_file'] || (!is_hr() && (int)$a['user_id'] !== (int)$u['id'])) {
    http_response_code(404);
    die('File not found.');
}
$path = UPLOAD_DIR . basename($a['cv_file']);
if (!is_file($path)) { http_response_code(404); die('File not found.'); }
$ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
$types = ['pdf' => 'application/pdf', 'doc' => 'application/msword', 'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
$safe = preg_replace('/[^A-Za-z0-9_-]+/', '_', $a['full_name']);
header('Content-Type: ' . ($types[$ext] ?? 'application/octet-stream'));
header('Content-Disposition: attachment; filename="CV_' . $safe . '.' . $ext . '"');
header('Content-Length: ' . filesize($path));
header('X-Content-Type-Options: nosniff');
readfile($path);
exit;
