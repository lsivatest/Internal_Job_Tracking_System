<?php
require_once 'includes/functions.php';
require_login();
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$v = q_one("SELECT v.*, d.name AS dept_name, r.full_name AS requester FROM vacancies v JOIN departments d ON d.id = v.department_id JOIN users r ON r.id = v.requested_by WHERE v.id = ?", [$id]);
if (!$v) { flash('error', 'That vacancy was not found.'); redirect('vacancies.php'); }

$canView = is_hr() || $v['status'] === 'Published' || ($u['role'] === 'manager' && (int)$v['department_id'] === (int)$u['department_id']);
if (!$canView) { flash('error', 'You do not have access to that vacancy.'); redirect('vacancies.php'); }

$canApply = in_array($u['role'], ['employee', 'manager'], true);
$already = q_one("SELECT id, status FROM applications WHERE vacancy_id = ? AND user_id = ?", [$id, $u['id']]);
$isOpen = $v['status'] === 'Published' && $v['closing_date'] >= date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (!$canApply || !$isOpen || $already) {
        flash('error', 'You cannot apply for this vacancy.');
        redirect('vacancy_view.php?id=' . $id);
    }
    $note = trim($_POST['cover_note'] ?? '');
    if ($note === '' || mb_strlen($note) > 2000) {
        flash('error', 'Write a short note (up to 2000 characters) explaining why you are applying.');
        redirect('vacancy_view.php?id=' . $id);
    }
    list($file, $err) = save_cv($_FILES['cv'] ?? null);
    if ($err) { flash('error', $err); redirect('vacancy_view.php?id=' . $id); }
    try {
        q_run("INSERT INTO applications (vacancy_id, user_id, cover_note, cv_file) VALUES (?, ?, ?, ?)", [$id, $u['id'], $note, $file]);
    } catch (PDOException $ex) {
        flash('error', 'You have already applied for this vacancy.');
        redirect('vacancy_view.php?id=' . $id);
    }
    notify_hr($u['full_name'] . ' applied for ' . $v['title'] . '.', 'applications.php');
    log_action('Application', 'Applied for: ' . $v['title']);
    flash('success', 'Your application was sent. You can follow its progress under My applications.');
    redirect('my_applications.php');
}

$pageTitle = $v['title'];
$apps = [];
$selected = [];
if (is_hr()) {
    $apps = q_all("SELECT a.id, a.status, a.applied_at, u.full_name, d.name AS from_dept FROM applications a JOIN users u ON u.id = a.user_id JOIN departments d ON d.id = u.department_id WHERE a.vacancy_id = ? ORDER BY a.applied_at", [$id]);
}
if (is_hr() || ($u['role'] === 'manager' && (int)$v['department_id'] === (int)$u['department_id'])) {
    $selected = q_all("SELECT u.full_name, d.name AS from_dept FROM applications a JOIN users u ON u.id = a.user_id JOIN departments d ON d.id = u.department_id WHERE a.vacancy_id = ? AND a.status = 'Selected'", [$id]);
}
include 'includes/header.php';
?>
<section class="panel">
  <div class="panel-head">
    <div><h2 style="font-size:1.5rem"><?= e($v['title']) ?></h2><p class="sub" style="margin:4px 0 0"><?= e($v['dept_name']) ?> department</p></div>
    <div class="actions"><?= badge($v['status']) ?><?php if (is_hr()): ?><a class="btn ghost sm" href="hr_requests.php?status=<?= e(urlencode($v['status'])) ?>">Manage request</a><?php endif; ?></div>
  </div>
  <div class="meta">
    <div><span>Positions</span><strong><?= (int)$v['positions'] ?></strong></div>
    <div><span>Closing date</span><strong><?= e(fmt_date($v['closing_date'])) ?></strong></div>
    <div><span>Requested by</span><strong><?= e($v['requester']) ?></strong></div>
    <div><span>Published</span><strong><?= e(fmt_date($v['published_at'])) ?></strong></div>
  </div>
</section>

<div class="grid-2">
  <section class="panel"><h2>About the job</h2><p class="prose"><?= e($v['description']) ?></p></section>
  <section class="panel"><h2>Qualifications</h2><p class="prose"><?= e($v['qualifications']) ?></p></section>
</div>

<?php if ($v['hr_comment'] && (is_hr() || $u['role'] === 'manager')): ?>
<section class="panel"><h2>HR comment</h2><p class="prose"><?= e($v['hr_comment']) ?></p></section>
<?php endif; ?>

<?php if ($selected): ?>
<section class="panel"><h2>Selected</h2>
  <ul class="note-list"><?php foreach ($selected as $s): ?><li><strong><?= e($s['full_name']) ?></strong> <span class="muted">from <?= e($s['from_dept']) ?></span></li><?php endforeach; ?></ul>
</section>
<?php endif; ?>

<?php if ($canApply): ?>
<section class="panel">
  <?php if ($already): ?>
    <h2>You have applied</h2>
    <p class="sub">Current status: <?= badge($already['status']) ?></p>
    <div class="tracker-lg"><?= tracker($already['status']) ?></div>
    <p><a class="btn ghost sm" href="application_view.php?id=<?= (int)$already['id'] ?>">View your application</a></p>
  <?php elseif ($isOpen): ?>
    <h2>Apply for this job</h2>
    <p class="sub">Human Resources will review your application and update its status here.</p>
    <form method="post" enctype="multipart/form-data" class="form-narrow">
      <?= csrf_field() ?>
      <div class="field"><label for="cover_note">Why are you applying?</label><textarea id="cover_note" name="cover_note" maxlength="2000" required></textarea></div>
      <div class="field"><label for="cv">CV (optional)</label><input type="file" id="cv" name="cv" accept=".pdf,.doc,.docx"><span class="hint">PDF, DOC or DOCX, up to 2 MB.</span></div>
      <button class="btn" type="submit">Send application</button>
    </form>
  <?php else: ?>
    <h2>Applications are closed</h2>
    <p class="sub">This vacancy is no longer accepting applications.</p>
  <?php endif; ?>
</section>
<?php endif; ?>

<?php if (is_hr()): ?>
<section class="panel">
  <h2>Applications for this vacancy</h2>
  <?php if (!$apps): ?><p class="empty">No one has applied yet.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Applicant</th><th>Current department</th><th>Applied</th><th>Progress</th><th></th></tr></thead>
    <tbody><?php foreach ($apps as $a): ?>
      <tr><td><?= e($a['full_name']) ?></td><td><?= e($a['from_dept']) ?></td><td><?= e(fmt_date($a['applied_at'])) ?></td><td><?= tracker($a['status'], true) ?></td><td><a class="btn ghost sm" href="application_view.php?id=<?= (int)$a['id'] ?>">Open</a></td></tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>
<?php endif; ?>
<?php include 'includes/footer.php'; ?>
