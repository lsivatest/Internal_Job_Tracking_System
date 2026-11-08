<?php
require_once 'includes/functions.php';
require_role(['employee', 'manager']);
$u = current_user();
$pageTitle = 'My applications';
$apps = q_all("SELECT a.id, a.status, a.applied_at, v.title, d.name AS dept FROM applications a JOIN vacancies v ON v.id = a.vacancy_id JOIN departments d ON d.id = v.department_id WHERE a.user_id = ? ORDER BY a.applied_at DESC", [$u['id']]);
include 'includes/header.php';
?>
<section class="panel">
  <?php if (!$apps): ?><p class="empty">You have not applied for any vacancy yet. <a href="vacancies.php">Browse open vacancies</a>.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Job title</th><th>Department</th><th>Applied</th><th>Status</th><th>Progress</th><th></th></tr></thead>
    <tbody><?php foreach ($apps as $a): ?>
      <tr><td><strong><?= e($a['title']) ?></strong></td><td><?= e($a['dept']) ?></td><td><?= e(fmt_date($a['applied_at'])) ?></td><td><?= badge($a['status']) ?></td><td><?= tracker($a['status'], true) ?></td><td><a class="btn ghost sm" href="application_view.php?id=<?= (int)$a['id'] ?>">View</a></td></tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
