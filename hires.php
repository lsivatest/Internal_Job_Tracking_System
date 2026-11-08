<?php
require_once 'includes/functions.php';
require_role(['hr', 'admin', 'manager']);
$u = current_user();
$pageTitle = 'Hiring records';
$year = (int)($_GET['year'] ?? 0);
$dept = (int)($_GET['dept'] ?? 0);
if ($u['role'] === 'manager') { $dept = (int)$u['department_id']; }

$sql = "SELECT a.id, u.full_name, ud.name AS from_dept, v.title, vd.name AS to_dept, hd.decided_at, dec.full_name AS decided_by
        FROM applications a JOIN users u ON u.id = a.user_id JOIN departments ud ON ud.id = u.department_id
        JOIN vacancies v ON v.id = a.vacancy_id JOIN departments vd ON vd.id = v.department_id
        LEFT JOIN hiring_decisions hd ON hd.id = (SELECT MAX(h2.id) FROM hiring_decisions h2 WHERE h2.application_id = a.id)
        LEFT JOIN users dec ON dec.id = hd.decided_by
        WHERE a.status = 'Selected'";
$p = [];
if ($dept > 0) { $sql .= " AND v.department_id = ?"; $p[] = $dept; }
if ($year > 0) { $sql .= " AND YEAR(hd.decided_at) = ?"; $p[] = $year; }
$sql .= " ORDER BY hd.decided_at DESC LIMIT 500";
$rows = q_all($sql, $p);

if (is_hr() && ($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="hiring_records_' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Employee', 'Previous department', 'Job title', 'Hiring department', 'Decision date', 'Decided by']);
    foreach ($rows as $r) { fputcsv($out, [$r['full_name'], $r['from_dept'], $r['title'], $r['to_dept'], $r['decided_at'], $r['decided_by']]); }
    fclose($out);
    exit;
}
$depts = q_all("SELECT * FROM departments ORDER BY name");
$years = q_all("SELECT DISTINCT YEAR(decided_at) AS y FROM hiring_decisions ORDER BY y DESC");
$qs = http_build_query(['dept' => $dept, 'year' => $year, 'export' => 'csv']);
include 'includes/header.php';
?>
<form class="filters" method="get">
  <?php if (is_hr()): ?>
  <div class="field"><label for="dept">Hiring department</label><select id="dept" name="dept"><option value="0">All departments</option>
    <?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $dept === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
  <?php endif; ?>
  <div class="field"><label for="year">Year</label><select id="year" name="year"><option value="0">All years</option>
    <?php foreach ($years as $y): ?><option <?= $year === (int)$y['y'] ? 'selected' : '' ?>><?= (int)$y['y'] ?></option><?php endforeach; ?></select></div>
  <button class="btn" type="submit">Filter</button>
  <?php if (is_hr()): ?><a class="btn ghost" href="hires.php?<?= e($qs) ?>">Export CSV</a><?php endif; ?>
</form>
<section class="panel">
  <h2>Who got which job</h2>
  <p class="sub">Every selected applicant, with the job and department they were appointed to.</p>
  <?php if (!$rows): ?><p class="empty">No hiring decisions match these filters yet.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Employee</th><th>Previous department</th><th>Appointed as</th><th>Hiring department</th><th>Decision date</th><th>Decided by</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr><td><strong><?= e($r['full_name']) ?></strong></td><td><?= e($r['from_dept']) ?></td><td><?= e($r['title']) ?></td><td><?= e($r['to_dept']) ?></td><td><?= e(fmt_date($r['decided_at'])) ?></td><td><?= e($r['decided_by']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
