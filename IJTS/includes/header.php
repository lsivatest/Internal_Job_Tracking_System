<?php
$u = current_user();
$page = basename($_SERVER['PHP_SELF']);
$nav = [];
$nav[] = ['dashboard.php', 'Dashboard', 'dashboard'];
$nav[] = ['vacancies.php', 'Open vacancies', 'briefcase'];
if (in_array($u['role'], ['employee', 'manager'], true)) { $nav[] = ['my_applications.php', 'My applications', 'file']; }
if (in_array($u['role'], ['manager', 'hr', 'admin'], true)) {
    $nav[] = ['request_new.php', 'New vacancy request', 'plus'];
    $nav[] = ['my_requests.php', 'Department requests', 'inbox'];
}
if (is_hr()) {
    $nav[] = ['hr_requests.php', 'Review requests', 'check'];
    $nav[] = ['applications.php', 'Applications', 'file'];
    $nav[] = ['hires.php', 'Hiring records', 'users'];
    $nav[] = ['reports.php', 'Reports', 'chart'];
}
if ($u['role'] === 'admin') {
    $nav[] = ['users.php', 'Users', 'users'];
    $nav[] = ['audit.php', 'Audit log', 'list'];
}
$unread = (int)q_val("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0", [$u['id']]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle ?? 'Dashboard') ?> | <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <a class="brand" href="dashboard.php">
      <span class="mark" aria-hidden="true"><i></i><i></i><i></i></span>
      <span class="brand-text"><strong><?= e(APP_NAME) ?></strong><small>Internal job tracking</small></span>
    </a>
    <nav class="nav" aria-label="Main">
      <?php foreach ($nav as $n): ?>
        <a href="<?= e($n[0]) ?>" class="<?= $page === $n[0] ? 'active' : '' ?>"><?= icon($n[2]) ?><span><?= e($n[1]) ?></span></a>
      <?php endforeach; ?>
    </nav>
    <div class="side-user">
      <strong><?= e($u['full_name']) ?></strong>
      <span><?= e(role_label($u['role'])) ?></span>
      <span><?= e($u['dept_name']) ?></span>
      <a href="logout.php" class="logout"><?= icon('logout') ?> Sign out</a>
    </div>
  </aside>
  <div class="main">
    <header class="topbar">
      <button class="menu-btn" id="menuBtn" aria-label="Open menu"><?= icon('menu') ?></button>
      <h1><?= e($pageTitle ?? 'Dashboard') ?></h1>
      <a class="bell" href="notifications.php" aria-label="Notifications"><?= icon('bell') ?><?php if ($unread > 0): ?><em><?= $unread ?></em><?php endif; ?></a>
    </header>
    <main class="content">
      <?php foreach (flash() as $f): ?>
        <div class="alert alert-<?= e($f['t']) ?>" role="status"><?= e($f['m']) ?></div>
      <?php endforeach; ?>
