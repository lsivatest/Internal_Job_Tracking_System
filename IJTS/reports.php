<?php
require_once 'includes/functions.php';
require_role(['hr', 'admin']);
$pageTitle = 'Reports';
$depts = dept_summary();
$appStatus = [];
foreach (q_all("SELECT status, COUNT(*) AS c FROM applications GROUP BY status") as $r) { $appStatus[$r['status']] = (int)$r['c']; }
$vacStatus = [];
foreach (q_all("SELECT status, COUNT(*) AS c FROM vacancies GROUP BY status") as $r) { $vacStatus[$r['status']] = (int)$r['c']; }
$avgDays = q_val("SELECT ROUND(AVG(DATEDIFF(hd.decided_at, v.published_at)), 1)
    FROM applications a JOIN vacancies v ON v.id = a.vacancy_id
    JOIN hiring_decisions hd ON hd.id = (SELECT MAX(h2.id) FROM hiring_decisions h2 WHERE h2.application_id = a.id)
    WHERE a.status = 'Selected' AND v.published_at IS NOT NULL");
$totalApps = array_sum($appStatus);
$totalVac = array_sum($vacStatus);
include 'includes/header.php';
?>
<div class="stats">
  <div class="stat"><span class="num"><?= (int)$totalVac ?></span><span class="lab">Vacancy requests in total</span></div>
  <div class="stat"><span class="num"><?= (int)$totalApps ?></span><span class="lab">Applications in total</span></div>
  <div class="stat"><span class="num"><?= $avgDays !== null && $avgDays !== false ? e($avgDays) : '-' ?></span><span class="lab">Average days from publishing to hiring decision</span></div>
</div>
<div class="grid-2">
  <section class="panel"><h2>Vacancies by status</h2><p class="sub">Across all five departments.</p>
    <div class="bars"><?php foreach (['Pending','Approved','Published','Returned','Rejected','Closed','Filled'] as $s): $c = $vacStatus[$s] ?? 0; $w = $totalVac ? round($c / $totalVac * 100) : 0; ?>
      <div class="row"><span><?= e($s) ?></span><div class="bar"><span style="width:<?= (int)$w ?>%"></span></div><strong><?= (int)$c ?></strong></div>
    <?php endforeach; ?></div></section>
  <section class="panel"><h2>Applications by status</h2><p class="sub">Where applicants are in the process.</p>
    <div class="bars"><?php foreach (['Received','Shortlisted','Interview','Selected','Rejected','On Hold'] as $s): $c = $appStatus[$s] ?? 0; $w = $totalApps ? round($c / $totalApps * 100) : 0; ?>
      <div class="row"><span><?= e($s) ?></span><div class="bar"><span style="width:<?= (int)$w ?>%"></span></div><strong><?= (int)$c ?></strong></div>
    <?php endforeach; ?></div></section>
</div>
<section class="panel">
  <div class="panel-head"><div><h2>Department summary</h2></div><a class="btn ghost sm" href="hires.php?export=csv">Export hiring records (CSV)</a></div>
  <div class="table-wrap"><table>
    <thead><tr><th>Department</th><th class="num">Requests</th><th class="num">Open now</th><th class="num">Filled</th><th class="num">Applications</th><th class="num">Selected</th></tr></thead>
    <tbody><?php foreach ($depts as $d): ?>
      <tr><td><?= e($d['name']) ?></td><td class="num"><?= (int)$d['total_v'] ?></td><td class="num"><?= (int)$d['open_v'] ?></td><td class="num"><?= (int)$d['filled_v'] ?></td><td class="num"><?= (int)$d['apps'] ?></td><td class="num"><?= (int)$d['hired'] ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div>
  <p class="muted small" style="margin-bottom:0">Use your browser's print option to save this page as a PDF.</p>
</section>
<?php include 'includes/footer.php'; ?>
