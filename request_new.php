<?php
require_once 'includes/functions.php';
require_role(['manager', 'hr', 'admin']);
$u = current_user();

$edit = null;
if (isset($_GET['id'])) {
    $edit = q_one("SELECT * FROM vacancies WHERE id = ? AND department_id = ? AND status = 'Returned'", [(int)$_GET['id'], $u['department_id']]);
    if (!$edit) { flash('error', 'That request cannot be edited.'); redirect('my_requests.php'); }
}
$pageTitle = $edit ? 'Update and resubmit request' : 'New vacancy request';
$f = [
    'title' => $edit['title'] ?? '', 'positions' => $edit['positions'] ?? 1,
    'description' => $edit['description'] ?? '', 'qualifications' => $edit['qualifications'] ?? '',
    'justification' => $edit['justification'] ?? '', 'closing_date' => $edit['closing_date'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    foreach ($f as $k => $_) { $f[$k] = trim($_POST[$k] ?? ''); }
    $errors = [];
    if (mb_strlen($f['title']) < 3 || mb_strlen($f['title']) > 150) { $errors[] = 'Enter a job title between 3 and 150 characters.'; }
    if (!ctype_digit((string)$f['positions']) || (int)$f['positions'] < 1 || (int)$f['positions'] > 50) { $errors[] = 'Positions must be a number from 1 to 50.'; }
    if ($f['description'] === '') { $errors[] = 'Describe the job.'; }
    if ($f['qualifications'] === '') { $errors[] = 'List the required qualifications.'; }
    if (!valid_date($f['closing_date']) || $f['closing_date'] < date('Y-m-d')) { $errors[] = 'Choose a closing date that is today or later.'; }
    if ($errors) {
        foreach ($errors as $er) { flash('error', $er); }
    } else {
        if ($edit) {
            q_run("UPDATE vacancies SET title=?, positions=?, description=?, qualifications=?, justification=?, closing_date=?, status='Pending', hr_comment=NULL WHERE id=?",
                [$f['title'], (int)$f['positions'], $f['description'], $f['qualifications'], $f['justification'], $f['closing_date'], $edit['id']]);
            log_action('Vacancy request', 'Resubmitted request: ' . $f['title']);
            notify_hr($u['dept_name'] . ' resubmitted the request "' . $f['title'] . '".', 'hr_requests.php');
            flash('success', 'Your request was resubmitted to Human Resources.');
        } else {
            q_run("INSERT INTO vacancies (title, department_id, requested_by, positions, description, qualifications, justification, closing_date, status) VALUES (?,?,?,?,?,?,?,?, 'Pending')",
                [$f['title'], $u['department_id'], $u['id'], (int)$f['positions'], $f['description'], $f['qualifications'], $f['justification'], $f['closing_date']]);
            log_action('Vacancy request', 'Created request: ' . $f['title']);
            notify_hr($u['dept_name'] . ' sent a new request: "' . $f['title'] . '".', 'hr_requests.php');
            flash('success', 'Your request was sent to Human Resources for review.');
        }
        redirect('my_requests.php');
    }
}
include 'includes/header.php';
?>
<section class="panel form-narrow">
  <h2><?= e($pageTitle) ?></h2>
  <p class="sub">Human Resources reviews every request and publishes the approved ones to all employees.</p>
  <?php if ($edit && $edit['hr_comment']): ?><div class="alert alert-info"><strong>HR asked for changes:</strong> <?= e($edit['hr_comment']) ?></div><?php endif; ?>
  <form method="post">
    <?= csrf_field() ?>
    <div class="field"><label>Department</label><input type="text" value="<?= e($u['dept_name']) ?>" disabled></div>
    <div class="form-grid">
      <div class="field"><label for="title">Job title</label><input type="text" id="title" name="title" value="<?= e($f['title']) ?>" maxlength="150" required></div>
      <div class="field"><label for="positions">Number of positions</label><input type="number" id="positions" name="positions" min="1" max="50" value="<?= e($f['positions']) ?>" required></div>
    </div>
    <div class="field"><label for="description">Job description</label><textarea id="description" name="description" required><?= e($f['description']) ?></textarea></div>
    <div class="field"><label for="qualifications">Required qualifications</label><textarea id="qualifications" name="qualifications" required><?= e($f['qualifications']) ?></textarea></div>
    <div class="field"><label for="justification">Reason for hiring</label><textarea id="justification" name="justification"><?= e($f['justification']) ?></textarea><span class="hint">For example: a replacement, a new project or extra workload.</span></div>
    <div class="field" style="max-width:260px"><label for="closing_date">Closing date for applications</label><input type="date" id="closing_date" name="closing_date" value="<?= e($f['closing_date']) ?>" min="<?= date('Y-m-d') ?>" required></div>
    <div class="actions"><button class="btn" type="submit"><?= $edit ? 'Resubmit request' : 'Send request to HR' ?></button><a class="btn ghost" href="my_requests.php">Cancel</a></div>
  </form>
</section>
<?php include 'includes/footer.php'; ?>
