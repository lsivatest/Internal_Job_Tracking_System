<?php
require_once 'includes/functions.php';
require_role(['manager', 'hr', 'admin']);
$u = current_user();
$pageTitle = 'Department requests';
$rows = q_all("SELECT v.*, r.full_name AS requester,
    (SELECT COUNT(*) FROM applications a WHERE a.vacancy_id = v.id) AS apps,
    (SELECT GROUP_CONCAT(x.full_name SEPARATOR ', ') FROM applications a JOIN users x ON x.id = a.user_id WHERE a.vacancy_id = v.id AND a.status = 'Selected') AS selected_names
    FROM vacancies v JOIN users r ON r.id = v.requested_by
    WHERE v.department_id = ? ORDER BY v.created_at DESC LIMIT 200", [$u['department_id']]);
include 'includes/header.php';
?>
<section class="panel">
  <div class="panel-head"><div><h2><?= e($u['dept_name']) ?> requests</h2><p class="sub" style="margin:0">Track each request from review to the final hire.</p></div><a class="btn sm" href="request_new.php">New vacancy request</a></div>
  <?php if (!$rows): ?><p class="empty">No requests yet. <a href="request_new.php">Send a vacancy request</a> to Human Resources.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Job title</th><th>Status</th><th>Closing date</th><th class="num">Applicants</th><th>Selected</th><th>HR comment</th><th></th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr>
        <td><a href="vacancy_view.php?id=<?= (int)$r['id'] ?>"><strong><?= e($r['title']) ?></strong></a><br><span class="muted small">by <?= e($r['requester']) ?></span></td>
        <td><?= badge($r['status']) ?></td>
        <td><?= e(fmt_date($r['closing_date'])) ?></td>
        <td class="num"><?= (int)$r['apps'] ?></td>
        <td><?= $r['selected_names'] ? e($r['selected_names']) : '<span class="muted">Not yet</span>' ?></td>
        <td class="small"><?= e($r['hr_comment'] ?? '') ?></td>
        <td><?php if ($r['status'] === 'Returned'): ?><a class="btn sm" href="request_new.php?id=<?= (int)$r['id'] ?>">Update and resubmit</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
