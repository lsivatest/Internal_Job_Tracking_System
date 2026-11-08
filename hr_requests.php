<?php
require_once 'includes/functions.php';
require_role(['hr', 'admin']);
$u = current_user();
$pageTitle = 'Review requests';

$rules = [
    'approve'         => ['from' => ['Pending'],            'to' => 'Approved',  'need' => false, 'text' => 'approved'],
    'publish'         => ['from' => ['Approved'],           'to' => 'Published', 'need' => false, 'text' => 'published'],
    'approve_publish' => ['from' => ['Pending'],            'to' => 'Published', 'need' => false, 'text' => 'approved and published'],
    'return'          => ['from' => ['Pending', 'Approved'], 'to' => 'Returned',  'need' => true,  'text' => 'returned for changes'],
    'reject'          => ['from' => ['Pending', 'Approved'], 'to' => 'Rejected',  'need' => true,  'text' => 'rejected'],
    'close'           => ['from' => ['Published'],          'to' => 'Closed',    'need' => false, 'text' => 'closed'],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int)($_POST['id'] ?? 0);
    $act = $_POST['action'] ?? '';
    $comment = trim($_POST['comment'] ?? '');
    $v = q_one("SELECT * FROM vacancies WHERE id = ?", [$id]);
    if (!$v || !isset($rules[$act])) {
        flash('error', 'That action is not available.');
    } elseif (!in_array($v['status'], $rules[$act]['from'], true)) {
        flash('error', 'This request is already ' . strtolower($v['status']) . ', so that action is not available.');
    } elseif ($rules[$act]['need'] && $comment === '') {
        flash('error', 'Write a comment so the department knows what to change.');
    } else {
        $to = $rules[$act]['to'];
        q_run("UPDATE vacancies SET status = ?, hr_comment = ?, reviewed_by = ?, published_at = CASE WHEN ? = 'Published' THEN NOW() ELSE published_at END WHERE id = ?",
            [$to, $comment !== '' ? $comment : $v['hr_comment'], $u['id'], $to, $id]);
        notify($v['requested_by'], 'Your request "' . $v['title'] . '" was ' . $rules[$act]['text'] . '.', 'my_requests.php');
        log_action('Vacancy ' . strtolower($to), $v['title'] . ' was ' . $rules[$act]['text']);
        flash('success', '"' . $v['title'] . '" was ' . $rules[$act]['text'] . '.');
    }
    redirect('hr_requests.php?status=' . urlencode($_POST['back'] ?? 'Pending'));
}

$statuses = ['Pending', 'Approved', 'Published', 'Returned', 'Rejected', 'Closed', 'Filled'];
$status = $_GET['status'] ?? 'Pending';
if ($status !== 'All' && !in_array($status, $statuses, true)) { $status = 'Pending'; }
$sql = "SELECT v.*, d.name AS dept_name, r.full_name AS requester,
        (SELECT COUNT(*) FROM applications a WHERE a.vacancy_id = v.id) AS apps
        FROM vacancies v JOIN departments d ON d.id = v.department_id JOIN users r ON r.id = v.requested_by";
$p = [];
if ($status !== 'All') { $sql .= " WHERE v.status = ?"; $p[] = $status; }
$sql .= " ORDER BY v.created_at DESC LIMIT 100";
$rows = q_all($sql, $p);
$counts = [];
foreach (q_all("SELECT status, COUNT(*) AS c FROM vacancies GROUP BY status") as $c) { $counts[$c['status']] = (int)$c['c']; }
include 'includes/header.php';
?>
<div class="tabs">
  <?php foreach (array_merge($statuses, ['All']) as $s): ?>
    <a href="hr_requests.php?status=<?= urlencode($s) ?>" class="<?= $status === $s ? 'on' : '' ?>"><?= e($s) ?><?= $s !== 'All' ? ' (' . ($counts[$s] ?? 0) . ')' : '' ?></a>
  <?php endforeach; ?>
</div>

<?php if (!$rows): ?>
  <section class="panel"><p class="empty">No <?= $status === 'All' ? '' : e(strtolower($status)) . ' ' ?>requests right now.</p></section>
<?php endif; ?>

<?php foreach ($rows as $r): ?>
<section class="panel">
  <div class="panel-head">
    <div><h2><a href="vacancy_view.php?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a></h2>
      <p class="sub" style="margin:2px 0 0"><?= e($r['dept_name']) ?> &middot; requested by <?= e($r['requester']) ?> on <?= e(fmt_date($r['created_at'])) ?></p></div>
    <?= badge($r['status']) ?>
  </div>
  <div class="meta">
    <div><span>Positions</span><strong><?= (int)$r['positions'] ?></strong></div>
    <div><span>Closing date</span><strong><?= e(fmt_date($r['closing_date'])) ?></strong></div>
    <div><span>Applicants</span><strong><?= (int)$r['apps'] ?></strong></div>
  </div>
  <?php if ($r['justification']): ?><p class="small"><strong>Reason for hiring:</strong> <?= e($r['justification']) ?></p><?php endif; ?>
  <details><summary>Show description and qualifications</summary>
    <p class="prose small"><strong>Description</strong><br><?= e($r['description']) ?></p>
    <p class="prose small"><strong>Qualifications</strong><br><?= e($r['qualifications']) ?></p>
  </details>
  <?php if ($r['hr_comment']): ?><p class="small"><strong>HR comment:</strong> <?= e($r['hr_comment']) ?></p><?php endif; ?>

  <?php if (in_array($r['status'], ['Pending', 'Approved', 'Published'], true)): ?>
  <form method="post" style="margin-top:14px">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
    <input type="hidden" name="back" value="<?= e($status) ?>">
    <?php if (in_array($r['status'], ['Pending', 'Approved'], true)): ?>
      <div class="field" style="max-width:560px"><label for="c<?= (int)$r['id'] ?>">Comment for the department</label><input type="text" id="c<?= (int)$r['id'] ?>" name="comment" maxlength="400"><span class="hint">Required when you return or reject a request.</span></div>
    <?php endif; ?>
    <div class="actions">
      <?php if ($r['status'] === 'Pending'): ?>
        <button class="btn" name="action" value="approve_publish" type="submit">Approve and publish</button>
        <button class="btn ghost" name="action" value="approve" type="submit">Approve only</button>
      <?php endif; ?>
      <?php if ($r['status'] === 'Approved'): ?><button class="btn" name="action" value="publish" type="submit">Publish to employees</button><?php endif; ?>
      <?php if (in_array($r['status'], ['Pending', 'Approved'], true)): ?>
        <button class="btn ghost" name="action" value="return" type="submit">Return for changes</button>
        <button class="btn danger" name="action" value="reject" type="submit" data-confirm="Reject this request?">Reject</button>
      <?php endif; ?>
      <?php if ($r['status'] === 'Published'): ?><button class="btn ghost" name="action" value="close" type="submit" data-confirm="Close this vacancy to new applications?">Close vacancy</button><?php endif; ?>
    </div>
  </form>
  <?php endif; ?>
</section>
<?php endforeach; ?>
<?php include 'includes/footer.php'; ?>
