<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax']);
    session_start();
}

/* ---------- Basic helpers ---------- */
function e($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function redirect($url) { header('Location: ' . $url); exit; }
function fmt_date($d) { return $d ? date('d M Y', strtotime($d)) : '-'; }
function fmt_datetime($d) { return $d ? date('d M Y, H:i', strtotime($d)) : '-'; }
function valid_date($d) {
    $dt = DateTime::createFromFormat('Y-m-d', (string)$d);
    return $dt && $dt->format('Y-m-d') === $d;
}

/* ---------- Database shortcuts ---------- */
function q_all($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s->fetchAll(); }
function q_one($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); $r = $s->fetch(); return $r ? $r : null; }
function q_val($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s->fetchColumn(); }
function q_run($sql, $p = []) { $s = db()->prepare($sql); $s->execute($p); return $s->rowCount(); }

/* ---------- CSRF ---------- */
function csrf_token() {
    if (empty($_SESSION['csrf'])) { $_SESSION['csrf'] = bin2hex(random_bytes(32)); }
    return $_SESSION['csrf'];
}
function csrf_field() { return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $t = $_POST['csrf'] ?? '';
        if (!hash_equals($_SESSION['csrf'] ?? '', $t)) {
            http_response_code(400);
            die('The page expired. Go back, refresh it and try again.');
        }
    }
}

/* ---------- Flash messages ---------- */
function flash($type = null, $msg = null) {
    if ($type !== null) { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; return null; }
    $f = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $f;
}

/* ---------- Authentication and roles ---------- */
function current_user() {
    static $u = null;
    if ($u === null) {
        $u = false;
        if (!empty($_SESSION['uid'])) {
            $row = q_one("SELECT u.*, d.name AS dept_name FROM users u JOIN departments d ON d.id = u.department_id WHERE u.id = ? AND u.status = 'Active'", [$_SESSION['uid']]);
            if ($row) { $u = $row; }
        }
    }
    return $u ? $u : null;
}
function require_login() { if (!current_user()) { redirect('login.php'); } }
function is_hr() { $u = current_user(); return $u && in_array($u['role'], ['hr', 'admin'], true); }
function require_role($roles) {
    require_login();
    $u = current_user();
    if (!in_array($u['role'], $roles, true)) {
        flash('error', 'You do not have access to that page.');
        redirect('dashboard.php');
    }
}
function role_label($r) {
    $m = ['admin' => 'HR manager (administrator)', 'hr' => 'HR officer', 'manager' => 'Department head', 'employee' => 'Employee'];
    return $m[$r] ?? $r;
}

/* ---------- Audit log and notifications ---------- */
function log_action($action, $details = '', $userId = null) {
    if ($userId === null) { $cu = current_user(); $userId = $cu ? $cu['id'] : null; }
    q_run("INSERT INTO audit_log (user_id, action, details) VALUES (?, ?, ?)", [$userId, $action, $details]);
}
function notify($userId, $message, $link = null) {
    q_run("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)", [$userId, $message, $link]);
}
function notify_hr($message, $link = null) {
    foreach (q_all("SELECT id FROM users WHERE role IN ('hr','admin') AND status = 'Active'") as $r) {
        notify($r['id'], $message, $link);
    }
}

/* ---------- Display helpers ---------- */
function badge($status) {
    $cls = 'b-' . strtolower(str_replace(' ', '-', $status));
    return '<span class="badge ' . e($cls) . '">' . e($status) . '</span>';
}
function tracker($status, $mini = false) {
    $steps = ['Received', 'Shortlisted', 'Interview', 'Decision'];
    $map = ['Received' => 0, 'Shortlisted' => 1, 'Interview' => 2, 'Selected' => 3, 'Rejected' => 3, 'On Hold' => 3];
    $cur = $map[$status] ?? 0;
    $final = in_array($status, ['Selected', 'Rejected', 'On Hold'], true);
    $h = '<ol class="tracker' . ($mini ? ' mini' : '') . '" aria-label="Application progress: ' . e($status) . '">';
    foreach ($steps as $i => $s) {
        if ($i < $cur) { $cls = 'done'; }
        elseif ($i === $cur) { $cls = $final ? 'final-' . strtolower(str_replace(' ', '-', $status)) : 'current'; }
        else { $cls = ''; }
        $label = ($i === 3 && $final) ? $status : $s;
        $h .= '<li class="' . $cls . '"><span class="dot"></span><span class="lbl">' . e($label) . '</span></li>';
    }
    return $h . '</ol>';
}
function icon($name) {
    $p = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9"/><rect x="14" y="3" width="7" height="5"/><rect x="14" y="12" width="7" height="9"/><rect x="3" y="16" width="7" height="5"/>',
        'briefcase' => '<rect x="3" y="7" width="18" height="13" rx="2"/><path d="M9 7V5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2v2"/>',
        'file'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/>',
        'plus'      => '<path d="M12 5v14M5 12h14"/>',
        'inbox'     => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.5 5h13L22 12v6a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2v-6z"/>',
        'check'     => '<circle cx="12" cy="12" r="9"/><path d="M8 12l3 3 5-6"/>',
        'chart'     => '<path d="M4 20V10M10 20V4M16 20v-8M22 20H2"/>',
        'users'     => '<circle cx="9" cy="8" r="4"/><path d="M2 21a7 7 0 0 1 14 0"/><path d="M17 4a4 4 0 0 1 0 8M22 21a6 6 0 0 0-4-5.6"/>',
        'list'      => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
        'bell'      => '<path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10 21a2 2 0 0 0 4 0"/>',
        'logout'    => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
        'menu'      => '<path d="M3 6h18M3 12h18M3 18h18"/>',
    ];
    return '<svg class="ic" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$name] ?? '') . '</svg>';
}

/* ---------- File upload (CV) ---------- */
function save_cv($f) {
    if (!isset($f) || !is_array($f) || $f['error'] === UPLOAD_ERR_NO_FILE) { return [null, null]; }
    if ($f['error'] !== UPLOAD_ERR_OK) { return [null, 'The file could not be uploaded. Try again.']; }
    if ($f['size'] > MAX_UPLOAD) { return [null, 'The CV must be 2 MB or smaller.']; }
    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) { return [null, 'The CV must be a PDF, DOC or DOCX file.']; }
    if (!is_dir(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0755, true); }
    $name = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($f['tmp_name'], UPLOAD_DIR . $name)) { return [null, 'The file could not be saved on the server.']; }
    return [$name, null];
}

/* ---------- Shared report query ---------- */
function dept_summary() {
    return q_all("SELECT d.name,
        (SELECT COUNT(*) FROM vacancies v WHERE v.department_id = d.id) AS total_v,
        (SELECT COUNT(*) FROM vacancies v WHERE v.department_id = d.id AND v.status = 'Published') AS open_v,
        (SELECT COUNT(*) FROM vacancies v WHERE v.department_id = d.id AND v.status = 'Filled') AS filled_v,
        (SELECT COUNT(*) FROM applications a JOIN vacancies v ON v.id = a.vacancy_id WHERE v.department_id = d.id) AS apps,
        (SELECT COUNT(*) FROM applications a JOIN vacancies v ON v.id = a.vacancy_id WHERE v.department_id = d.id AND a.status = 'Selected') AS hired
        FROM departments d ORDER BY d.name");
}
