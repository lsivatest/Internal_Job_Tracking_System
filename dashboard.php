<?php
require_once 'includes/functions.php';
require_login();
$u = current_user();
$pageTitle = 'Dashboard';
include 'includes/header.php';
$notes = q_all("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 5", [$u['id']]);
?>
<p class="muted" style="margin-top:0">Welcome back, <?= e(explode(' ', $u['full_name'])[0]) ?>.</p>

<?php if (is_hr()):
    $pending = (int)q_val("SELECT COUNT(*) FROM vacancies WHERE status = 'Pending'");
    $open = (int)q_val("SELECT COUNT(*) FROM vacancies WHERE status = 'Published'");
    $inprog = (int)q_val("SELECT COUNT(*) FROM applications WHERE status IN ('Received','Shortlisted','Interview')");
    $hired = (int)q_val("SELECT COUNT(*) FROM applications WHERE status = 'Selected'");
    $queue = q_all("SELECT v.id, v.title, v.created_at, d.name AS dept FROM vacancies v JOIN departments d ON d.id = v.department_id WHERE v.status = 'Pending' ORDER BY v.created_at LIMIT 5");
    $recent = q_all("SELECT a.id, a.status, a.applied_at, u.full_name, v.title FROM applications a JOIN users u ON u.id = a.user_id JOIN vacancies v ON v.id = a.vacancy_id ORDER BY a.applied_at DESC LIMIT 6");
    $depts = dept_summary();
?>
  <div class="stats">
    <div class="stat"><span class="num"><?= $pending ?></span><span class="lab">Requests waiting for review</span></div>
    <div class="stat"><span class="num"><?= $open ?></span><span class="lab">Open vacancies</span></div>
    <div class="stat"><span class="num"><?= $inprog ?></span><span class="lab">Applications in progress</span></div>
    <div class="stat"><span class="num"><?= $hired ?></span><span class="lab">Employees selected</span></div>
  </div>
  <div class="grid-2">
    <section class="panel">
      <div class="panel-head"><div><h2>Requests to review</h2><p class="sub" style="margin:0">Oldest first</p></div><a class="btn ghost sm" href="hr_requests.php">Review requests</a></div>
      <?php if (!$queue): ?><p class="empty">No requests are waiting. New requests from departments appear here.</p>
      <?php else: ?><ul class="note-list">
        <?php foreach ($queue as $r): ?><li><a href="hr_requests.php"><?= e($r['title']) ?></a><small><?= e($r['dept']) ?> &middot; sent <?= e(fmt_date($r['created_at'])) ?></small></li><?php endforeach; ?>
      </ul><?php endif; ?>
    </section>
    <section class="panel">
      <div class="panel-head"><div><h2>Latest applications</h2></div><a class="btn ghost sm" href="applications.php">All applications</a></div>
      <?php if (!$recent): ?><p class="empty">No applications yet.</p>
      <?php else: ?><ul class="note-list">
        <?php foreach ($recent as $r): ?><li><a href="application_view.php?id=<?= (int)$r['id'] ?>"><?= e($r['full_name']) ?></a> applied for <?= e($r['title']) ?><small><?= badge($r['status']) ?> <?= e(fmt_date($r['applied_at'])) ?></small></li><?php endforeach; ?>
      </ul><?php endif; ?>
    </section>
  </div>
  <section class="panel">
    <h2>By department</h2>
    <p class="sub">Vacancies raised through Human Resources and their outcome.</p>
    <div class="table-wrap"><table>
      <thead><tr><th>Department</th><th class="num">Requests</th><th class="num">Open now</th><th class="num">Filled</th><th class="num">Applications</th><th class="num">Selected</th></tr></thead>
      <tbody><?php foreach ($depts as $d): ?>
        <tr><td><?= e($d['name']) ?></td><td class="num"><?= (int)$d['total_v'] ?></td><td class="num"><?= (int)$d['open_v'] ?></td><td class="num"><?= (int)$d['filled_v'] ?></td><td class="num"><?= (int)$d['apps'] ?></td><td class="num"><?= (int)$d['hired'] ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  </section>

<?php elseif ($u['role'] === 'manager'):
    $tot = (int)q_val("SELECT COUNT(*) FROM vacancies WHERE department_id = ?", [$u['department_id']]);
    $pen = (int)q_val("SELECT COUNT(*) FROM vacancies WHERE department_id = ? AND status = 'Pending'", [$u['department_id']]);
    $pub = (int)q_val("SELECT COUNT(*) FROM vacancies WHERE department_id = ? AND status = 'Published'", [$u['department_id']]);
    $fil = (int)q_val("SELECT COUNT(*) FROM vacancies WHERE department_id = ? AND status = 'Filled'", [$u['department_id']]);
    $mine = q_all("SELECT * FROM vacancies WHERE department_id = ? ORDER BY created_at DESC LIMIT 6", [$u['department_id']]);
?>
  <div class="stats">
    <div class="stat"><span class="num"><?= $tot ?></span><span class="lab">Requests from <?= e($u['dept_name']) ?></span></div>
    <div class="stat"><span class="num"><?= $pen ?></span><span class="lab">Waiting for HR</span></div>
    <div class="stat"><span class="num"><?= $pub ?></span><span class="lab">Open for applications</span></div>
    <div class="stat"><span class="num"><?= $fil ?></span><span class="lab">Filled</span></div>
  </div>
  <section class="panel">
    <div class="panel-head"><div><h2>Latest requests</h2></div><a class="btn sm" href="request_new.php">New vacancy request</a></div>
    <?php if (!$mine): ?><p class="empty">Your department has not raised a vacancy yet. <a href="request_new.php">Send your first request</a>.</p>
    <?php else: ?><div class="table-wrap"><table>
      <thead><tr><th>Job title</th><th>Status</th><th>Closing date</th></tr></thead>
      <tbody><?php foreach ($mine as $r): ?>
        <tr><td><a href="vacancy_view.php?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a></td><td><?= badge($r['status']) ?></td><td><?= e(fmt_date($r['closing_date'])) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div><?php endif; ?>
  </section>

<?php else:
    $apps = q_all("SELECT a.id, a.status, a.applied_at, v.title, d.name AS dept FROM applications a JOIN vacancies v ON v.id = a.vacancy_id JOIN departments d ON d.id = v.department_id WHERE a.user_id = ? ORDER BY a.applied_at DESC LIMIT 5", [$u['id']]);
    $open = (int)q_val("SELECT COUNT(*) FROM vacancies WHERE status = 'Published' AND closing_date >= CURDATE()");
?>
  <div class="stats">
    <div class="stat"><span class="num"><?= $open ?></span><span class="lab">Vacancies open to you</span></div>
    <div class="stat"><span class="num"><?= count($apps) ?></span><span class="lab">Your recent applications</span></div>
  </div>
  <section class="panel">
    <div class="panel-head"><div><h2>Where your applications stand</h2></div><a class="btn sm" href="vacancies.php">Browse vacancies</a></div>
    <?php if (!$apps): ?><p class="empty">You have not applied yet. <a href="vacancies.php">Browse open vacancies</a>.</p>
    <?php else: foreach ($apps as $a): ?>
      <div style="margin-bottom:22px">
        <a href="application_view.php?id=<?= (int)$a['id'] ?>"><strong><?= e($a['title']) ?></strong></a> <span class="muted small">&middot; <?= e($a['dept']) ?></span>
        <div class="tracker-lg"><?= tracker($a['status']) ?></div>
      </div>
    <?php endforeach; endif; ?>
  </section>
<?php endif; ?>

<section class="panel">
  <div class="panel-head"><div><h2>Notifications</h2></div><a class="btn ghost sm" href="notifications.php">See all</a></div>
  <?php if (!$notes): ?><p class="empty">Nothing new. Updates about your vacancies and applications appear here.</p>
  <?php else: ?><ul class="note-list">
    <?php foreach ($notes as $n): ?><li class="<?= $n['is_read'] ? '' : 'unread' ?>"><?= e($n['message']) ?><small><?= e(fmt_datetime($n['created_at'])) ?></small></li><?php endforeach; ?>
  </ul><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
