<?php
require_once 'includes/functions.php';
require_login();
$u = current_user();
$pageTitle = 'Notifications';
$notes = q_all("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 100", [$u['id']]);
include 'includes/header.php';
?>
<section class="panel">
  <?php if (!$notes): ?><p class="empty">You have no notifications yet.</p>
  <?php else: ?><ul class="note-list">
    <?php foreach ($notes as $n): ?>
      <li class="<?= $n['is_read'] ? '' : 'unread' ?>">
        <?php if ($n['link']): ?><a href="<?= e($n['link']) ?>"><?= e($n['message']) ?></a><?php else: ?><?= e($n['message']) ?><?php endif; ?>
        <small><?= e(fmt_datetime($n['created_at'])) ?></small>
      </li>
    <?php endforeach; ?>
  </ul><?php endif; ?>
</section>
<?php
q_run("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$u['id']]);
include 'includes/footer.php';
