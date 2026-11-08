<?php
require_once 'includes/functions.php';
require_login();
$u = current_user();
$id = (int)($_GET['id'] ?? 0);
$app = q_one("SELECT a.*, u.full_name, u.email, ud.name AS from_dept, v.title, v.positions, v.requested_by, v.status AS vstatus, vd.name AS to_dept
    FROM applications a JOIN users u ON u.id = a.user_id JOIN departments ud ON ud.id = u.department_id
    JOIN vacancies v ON v.id = a.vacancy_id JOIN departments vd ON vd.id = v.department_id WHERE a.id = ?", [$id]);
if (!$app) { flash('error', 'That application was not found.'); redirect('dashboard.php'); }
$hr = is_hr();
if (!$hr && (int)$app['user_id'] !== (int)$u['id']) { flash('error', 'You do not have access to that application.'); redirect('dashboard.php'); }
$self = 'application_view.php?id=' . $id;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$hr) { flash('error', 'Only Human Resources can change an application.'); redirect($self); }
    csrf_check();
    $act = $_POST['action'] ?? '';

    if ($act === 'shortlist') {
        if ($app['status'] === 'Received') {
            q_run("UPDATE applications SET status = 'Shortlisted' WHERE id = ?", [$id]);
            notify($app['user_id'], 'You were shortlisted for ' . $app['title'] . '.', 'application_view.php?id=' . $id);
            log_action('Shortlisted', $app['full_name'] . ' for ' . $app['title']);
            flash('success', $app['full_name'] . ' was shortlisted.');
        }
        redirect($self);
    }

    if ($act === 'interview') {
        $stage = $_POST['stage'] ?? '';
        $date = $_POST['interview_date'] ?? '';
        $panel = trim($_POST['panel'] ?? '');
        $marks = trim($_POST['marks'] ?? '');
        $result = $_POST['result'] ?? 'Pending';
        $comments = trim($_POST['comments'] ?? '');
        $errs = [];
        if (!in_array($stage, ['Screening', 'First Interview', 'Final Interview'], true)) { $errs[] = 'Choose an interview stage.'; }
        if (!valid_date($date)) { $errs[] = 'Enter a valid interview date.'; }
        if (!in_array($result, ['Pending', 'Passed', 'Failed'], true)) { $errs[] = 'Choose a result.'; }
        if ($marks !== '' && (!ctype_digit($marks) || (int)$marks > 100)) { $errs[] = 'Marks must be a whole number from 0 to 100.'; }
        if ($errs) { foreach ($errs as $er) { flash('error', $er); } redirect($self); }
        q_run("INSERT INTO interviews (application_id, stage, interview_date, panel, marks, result, comments, created_by) VALUES (?,?,?,?,?,?,?,?)",
            [$id, $stage, $date, $panel, $marks === '' ? null : (int)$marks, $result, $comments, $u['id']]);
        if (in_array($app['status'], ['Received', 'Shortlisted'], true)) {
            q_run("UPDATE applications SET status = 'Interview' WHERE id = ?", [$id]);
        }
        notify($app['user_id'], 'Interview stage recorded for ' . $app['title'] . ': ' . $stage . ' (' . $result . ').', 'application_view.php?id=' . $id);
        log_action('Interview stage', $stage . ' recorded for ' . $app['full_name'] . ' (' . $app['title'] . ')');
        flash('success', 'The ' . strtolower($stage) . ' was recorded.');
        redirect($self);
    }

    if ($act === 'decide') {
        $dec = $_POST['decision'] ?? '';
        $reason = trim($_POST['reason'] ?? '');
        if (!in_array($dec, ['Selected', 'Rejected', 'On Hold'], true) || $reason === '') {
            flash('error', 'Choose a decision and write the reason for it.');
            redirect($self);
        }
        $pdo = db();
        $pdo->beginTransaction();
        try {
            q_run("INSERT INTO hiring_decisions (application_id, decision, reason, decided_by) VALUES (?,?,?,?)", [$id, $dec, $reason, $u['id']]);
            q_run("UPDATE applications SET status = ? WHERE id = ?", [$dec, $id]);
            $sel = (int)q_val("SELECT COUNT(*) FROM applications WHERE vacancy_id = ? AND status = 'Selected'", [$app['vacancy_id']]);
            if ($sel >= (int)$app['positions']) {
                q_run("UPDATE vacancies SET status = 'Filled' WHERE id = ? AND status IN ('Published','Closed')", [$app['vacancy_id']]);
            } elseif ($app['vstatus'] === 'Filled') {
                q_run("UPDATE vacancies SET status = 'Published' WHERE id = ?", [$app['vacancy_id']]);
            }
            $pdo->commit();
        } catch (Throwable $ex) {
            $pdo->rollBack();
            flash('error', 'The decision could not be saved. Try again.');
            redirect($self);
        }
        notify($app['user_id'], 'Decision for ' . $app['title'] . ': ' . $dec . '.', 'application_view.php?id=' . $id);
        notify($app['requested_by'], $app['full_name'] . ' was marked "' . $dec . '" for ' . $app['title'] . '.', 'my_requests.php');
        log_action('Hiring decision', $dec . ': ' . $app['full_name'] . ' for ' . $app['title']);
        flash('success', 'Decision saved: ' . $dec . '.');
        redirect($self);
    }
    redirect($self);
}

$pageTitle = 'Application';
$interviews = q_all("SELECT i.*, c.full_name AS by_name FROM interviews i LEFT JOIN users c ON c.id = i.created_by WHERE i.application_id = ? ORDER BY i.interview_date, i.id", [$id]);
$decisions = q_all("SELECT h.*, x.full_name AS by_name FROM hiring_decisions h JOIN users x ON x.id = h.decided_by WHERE h.application_id = ? ORDER BY h.id DESC", [$id]);
include 'includes/header.php';
?>
<section class="panel">
  <div class="panel-head">
    <div><h2 style="font-size:1.5rem"><?= e($hr ? $app['full_name'] : $app['title']) ?></h2>
      <p class="sub" style="margin:4px 0 0"><?= $hr ? 'Applied for ' . e($app['title']) . ' (' . e($app['to_dept']) . ')' : e($app['to_dept']) . ' department' ?></p></div>
    <?= badge($app['status']) ?>
  </div>
  <div class="tracker-lg"><?= tracker($app['status']) ?></div>
  <div class="meta" style="margin-top:18px">
    <div><span>Applied on</span><strong><?= e(fmt_datetime($app['applied_at'])) ?></strong></div>
    <?php if ($hr): ?>
      <div><span>Email</span><strong><?= e($app['email']) ?></strong></div>
      <div><span>Current department</span><strong><?= e($app['from_dept']) ?></strong></div>
      <div><span>CV</span><strong><?= $app['cv_file'] ? '<a href="download.php?id=' . $id . '">Download CV</a>' : 'Not attached' ?></strong></div>
    <?php endif; ?>
  </div>
  <?php if ($app['cover_note']): ?><p class="prose" style="margin-bottom:0"><strong>Note from applicant</strong><br><?= e($app['cover_note']) ?></p><?php endif; ?>
</section>

<section class="panel">
  <h2>Interview stages</h2>
  <?php if (!$interviews): ?><p class="empty">No interview stage has been recorded yet.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Stage</th><th>Date</th><?php if ($hr): ?><th>Panel</th><th class="num">Marks</th><?php endif; ?><th>Result</th><?php if ($hr): ?><th>Comments</th><?php endif; ?></tr></thead>
    <tbody><?php foreach ($interviews as $i): ?>
      <tr><td><strong><?= e($i['stage']) ?></strong></td><td><?= e(fmt_date($i['interview_date'])) ?></td>
        <?php if ($hr): ?><td><?= e($i['panel']) ?></td><td class="num"><?= $i['marks'] === null ? '-' : (int)$i['marks'] ?></td><?php endif; ?>
        <td><?= badge($i['result']) ?></td><?php if ($hr): ?><td class="small"><?= e($i['comments']) ?></td><?php endif; ?></tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>

<?php if ($decisions): ?>
<section class="panel">
  <h2>Hiring decision</h2>
  <ul class="note-list">
  <?php foreach ($decisions as $d): ?>
    <li><?= badge($d['decision']) ?> <span class="muted small"><?= e(fmt_datetime($d['decided_at'])) ?><?= $hr ? ' by ' . e($d['by_name']) : '' ?></span>
      <?php if ($hr): ?><div class="small"><?= e($d['reason']) ?></div><?php endif; ?></li>
  <?php endforeach; ?>
  </ul>
</section>
<?php endif; ?>

<?php if ($hr): ?>
<div class="grid-2">
  <section class="panel">
    <h2>Record an interview stage</h2>
    <p class="sub">Each stage is saved with its date, panel and result.</p>
    <?php if ($app['status'] === 'Received'): ?>
      <form method="post" style="margin-bottom:16px"><?= csrf_field() ?><input type="hidden" name="action" value="shortlist"><button class="btn ghost sm" type="submit">Shortlist this applicant</button></form>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="interview">
      <div class="form-grid">
        <div class="field"><label for="stage">Stage</label><select id="stage" name="stage"><option>Screening</option><option>First Interview</option><option>Final Interview</option></select></div>
        <div class="field"><label for="interview_date">Date</label><input type="date" id="interview_date" name="interview_date" value="<?= date('Y-m-d') ?>" required></div>
        <div class="field"><label for="result">Result</label><select id="result" name="result"><option>Pending</option><option>Passed</option><option>Failed</option></select></div>
        <div class="field"><label for="marks">Marks (0 to 100)</label><input type="number" id="marks" name="marks" min="0" max="100"></div>
      </div>
      <div class="field"><label for="panel">Interview panel</label><input type="text" id="panel" name="panel" maxlength="200" placeholder="Names of the panel members"></div>
      <div class="field"><label for="comments">Comments</label><textarea id="comments" name="comments" style="min-height:80px"></textarea></div>
      <button class="btn" type="submit">Save interview stage</button>
    </form>
  </section>
  <section class="panel">
    <h2>Hiring decision</h2>
    <p class="sub">The department head and the applicant are notified automatically.</p>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="decide">
      <div class="field"><label for="decision">Decision</label><select id="decision" name="decision"><option>Selected</option><option>Rejected</option><option>On Hold</option></select></div>
      <div class="field"><label for="reason">Reason</label><textarea id="reason" name="reason" required></textarea></div>
      <button class="btn" type="submit" data-confirm="Save this hiring decision and notify the applicant?">Save decision</button>
    </form>
  </section>
</div>
<?php endif; ?>
<p><a href="<?= $hr ? 'applications.php' : 'my_applications.php' ?>">Back to applications</a></p>
<?php include 'includes/footer.php'; ?>
