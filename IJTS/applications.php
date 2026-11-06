<?php
require_once 'includes/functions.php';
require_role(['hr', 'admin']);
$pageTitle = 'Applications';
$q = trim($_GET['q'] ?? '');
$vid = (int)($_GET['vacancy'] ?? 0);
$status = $_GET['status'] ?? '';
$allowed = ['Received', 'Shortlisted', 'Interview', 'Selected', 'Rejected', 'On Hold'];
$sql = "SELECT a.id, a.status, a.applied_at, u.full_name, ud.name AS from_dept, v.title, vd.name AS to_dept
        FROM applications a JOIN users u ON u.id = a.user_id JOIN departments ud ON ud.id = u.department_id
        JOIN vacancies v ON v.id = a.vacancy_id JOIN departments vd ON vd.id = v.department_id WHERE 1=1";
$p = [];
if ($q !== '') { $sql .= " AND (u.full_name LIKE ? OR v.title LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
if ($vid > 0) { $sql .= " AND a.vacancy_id = ?"; $p[] = $vid; }
if (in_array($status, $allowed, true)) { $sql .= " AND a.status = ?"; $p[] = $status; }
$sql .= " ORDER BY a.applied_at DESC LIMIT 300";
$rows = q_all($sql, $p);
$vacs = q_all("SELECT id, title FROM vacancies WHERE status IN ('Published','Closed','Filled') ORDER BY title");
include 'includes/header.php';
?>
<form class="filters" method="get">
  <div class="field"><label for="q">Search</label><input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Applicant or job title"></div>
  <div class="field"><label for="vacancy">Vacancy</label><select id="vacancy" name="vacancy"><option value="0">All vacancies</option>
    <?php foreach ($vacs as $v): ?><option value="<?= (int)$v['id'] ?>" <?= $vid === (int)$v['id'] ? 'selected' : '' ?>><?= e($v['title']) ?></option><?php endforeach; ?></select></div>
  <div class="field"><label for="status">Status</label><select id="status" name="status"><option value="">All statuses</option>
    <?php foreach ($allowed as $s): ?><option <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option><?php endforeach; ?></select></div>
  <button class="btn" type="submit">Filter</button>
  <?php if ($q !== '' || $vid || $status !== ''): ?><a class="btn ghost" href="applications.php">Clear</a><?php endif; ?>
</form>
<section class="panel">
  <?php if (!$rows): ?><p class="empty">No applications match these filters.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Applicant</th><th>Applied for</th><th>Applied</th><th>Status</th><th>Progress</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr><td><strong><?= e($r['full_name']) ?></strong><br><span class="muted small"><?= e($r['from_dept']) ?></span></td>
        <td><?= e($r['title']) ?><br><span class="muted small"><?= e($r['to_dept']) ?></span></td>
        <td><?= e(fmt_date($r['applied_at'])) ?></td><td><?= badge($r['status']) ?></td><td><?= tracker($r['status'], true) ?></td>
        <td><a class="btn ghost sm" href="application_view.php?id=<?= (int)$r['id'] ?>">Open</a></td></tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
