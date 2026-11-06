<?php
require_once 'includes/functions.php';
require_role(['admin']);
$pageTitle = 'Audit log';
$rows = q_all("SELECT l.*, u.full_name FROM audit_log l LEFT JOIN users u ON u.id = l.user_id ORDER BY l.id DESC LIMIT 200");
include 'includes/header.php';
?>
<section class="panel">
  <h2>Recent activity</h2>
  <p class="sub">The latest 200 actions: who did what, and when.</p>
  <?php if (!$rows): ?><p class="empty">No activity has been recorded yet.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>When</th><th>User</th><th>Action</th><th>Details</th></tr></thead>
    <tbody><?php foreach ($rows as $r): ?>
      <tr><td class="small" style="white-space:nowrap"><?= e(fmt_datetime($r['created_at'])) ?></td><td><?= e($r['full_name'] ?? 'System') ?></td><td><?= e($r['action']) ?></td><td class="small"><?= e($r['details']) ?></td></tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
