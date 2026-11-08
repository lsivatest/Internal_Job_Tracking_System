<?php
require_once 'includes/functions.php';
require_role(['admin']);
$u = current_user();
$pageTitle = 'Users';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $act = $_POST['action'] ?? '';
    if ($act === 'add') {
        $name = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pw = $_POST['password'] ?? '';
        $role = $_POST['role'] ?? '';
        $dept = (int)($_POST['department_id'] ?? 0);
        $errs = [];
        if (mb_strlen($name) < 3) { $errs[] = 'Enter the full name.'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) { $errs[] = 'Enter a valid email address.'; }
        if (strlen($pw) < 8) { $errs[] = 'The password must have at least 8 characters.'; }
        if (!in_array($role, ['admin', 'hr', 'manager', 'employee'], true)) { $errs[] = 'Choose a role.'; }
        if (!q_val("SELECT COUNT(*) FROM departments WHERE id = ?", [$dept])) { $errs[] = 'Choose a department.'; }
        if ($errs) {
            foreach ($errs as $er) { flash('error', $er); }
        } else {
            try {
                q_run("INSERT INTO users (full_name, email, password_hash, role, department_id) VALUES (?,?,?,?,?)", [$name, $email, password_hash($pw, PASSWORD_DEFAULT), $role, $dept]);
                log_action('User created', $email . ' (' . $role . ')');
                flash('success', $name . ' was added.');
            } catch (PDOException $ex) {
                flash('error', 'That email address is already in use.');
            }
        }
    } elseif ($act === 'toggle') {
        $tid = (int)($_POST['id'] ?? 0);
        if ($tid === (int)$u['id']) {
            flash('error', 'You cannot deactivate your own account.');
        } else {
            q_run("UPDATE users SET status = IF(status = 'Active', 'Inactive', 'Active') WHERE id = ?", [$tid]);
            log_action('User status', 'Changed status of user #' . $tid);
            flash('success', 'The account status was updated.');
        }
    } elseif ($act === 'reset') {
        $tid = (int)($_POST['id'] ?? 0);
        $pw = $_POST['password'] ?? '';
        if (strlen($pw) < 8) {
            flash('error', 'The new password must have at least 8 characters.');
        } else {
            q_run("UPDATE users SET password_hash = ? WHERE id = ?", [password_hash($pw, PASSWORD_DEFAULT), $tid]);
            log_action('Password reset', 'Reset password for user #' . $tid);
            flash('success', 'The password was changed.');
        }
    }
    redirect('users.php');
}
$users = q_all("SELECT u.*, d.name AS dept_name FROM users u JOIN departments d ON d.id = u.department_id ORDER BY d.name, u.full_name");
$depts = q_all("SELECT * FROM departments ORDER BY name");
include 'includes/header.php';
?>
<section class="panel">
  <h2>Add a user</h2>
  <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="add">
    <div class="form-grid">
      <div class="field"><label for="full_name">Full name</label><input type="text" id="full_name" name="full_name" required></div>
      <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" required></div>
      <div class="field"><label for="role">Role</label><select id="role" name="role"><option value="employee">Employee</option><option value="manager">Department head</option><option value="hr">HR officer</option><option value="admin">HR manager (administrator)</option></select></div>
      <div class="field"><label for="department_id">Department</label><select id="department_id" name="department_id"><?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>"><?= e($d['name']) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label for="password">Temporary password</label><input type="password" id="password" name="password" minlength="8" required><span class="hint">At least 8 characters.</span></div>
    </div>
    <button class="btn" type="submit">Add user</button>
  </form>
</section>
<section class="panel">
  <h2>All users</h2>
  <div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Department</th><th>Status</th><th>Actions</th></tr></thead>
    <tbody><?php foreach ($users as $r): ?>
      <tr>
        <td><strong><?= e($r['full_name']) ?></strong></td><td><?= e($r['email']) ?></td><td><?= e(role_label($r['role'])) ?></td><td><?= e($r['dept_name']) ?></td>
        <td><?= $r['status'] === 'Active' ? '<span class="badge b-selected">Active</span>' : '<span class="badge b-closed">Inactive</span>' ?></td>
        <td>
          <form method="post" style="display:inline"><?= csrf_field() ?><input type="hidden" name="action" value="toggle"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
            <button class="btn ghost sm" type="submit" data-confirm="Change the status of this account?"><?= $r['status'] === 'Active' ? 'Deactivate' : 'Activate' ?></button></form>
          <details style="display:inline-block;margin-left:8px"><summary>Reset password</summary>
            <form method="post" class="actions" style="margin-top:6px"><?= csrf_field() ?><input type="hidden" name="action" value="reset"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <input type="password" name="password" minlength="8" placeholder="New password" aria-label="New password" style="width:160px" required><button class="btn sm" type="submit">Save</button></form></details>
        </td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div>
</section>
<?php include 'includes/footer.php'; ?>
