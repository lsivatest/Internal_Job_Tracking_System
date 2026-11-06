<?php
require_once 'includes/functions.php';
require_login();
$u = current_user();
$pageTitle = 'Open vacancies';
$q = trim($_GET['q'] ?? '');
$dept = (int)($_GET['dept'] ?? 0);
$sql = "SELECT v.*, d.name AS dept_name,
        (SELECT COUNT(*) FROM applications a WHERE a.vacancy_id = v.id) AS apps,
        (SELECT COUNT(*) FROM applications a WHERE a.vacancy_id = v.id AND a.user_id = ?) AS mine
        FROM vacancies v JOIN departments d ON d.id = v.department_id
        WHERE v.status = 'Published'";
$p = [$u['id']];
if ($q !== '') { $sql .= " AND (v.title LIKE ? OR v.description LIKE ?)"; $p[] = "%$q%"; $p[] = "%$q%"; }
if ($dept > 0) { $sql .= " AND v.department_id = ?"; $p[] = $dept; }
$sql .= " ORDER BY v.closing_date ASC LIMIT 200";
$rows = q_all($sql, $p);
$depts = q_all("SELECT * FROM departments ORDER BY name");
include 'includes/header.php';
?>
<form class="filters" method="get">
  <div class="field"><label for="q">Search</label><input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Job title or keyword"></div>
  <div class="field"><label for="dept">Department</label>
    <select id="dept" name="dept"><option value="0">All departments</option>
      <?php foreach ($depts as $d): ?><option value="<?= (int)$d['id'] ?>" <?= $dept === (int)$d['id'] ? 'selected' : '' ?>><?= e($d['name']) ?></option><?php endforeach; ?>
    </select></div>
  <button class="btn" type="submit">Search</button>
  <?php if ($q !== '' || $dept > 0): ?><a class="btn ghost" href="vacancies.php">Clear</a><?php endif; ?>
</form>
<section class="panel">
  <?php if (!$rows): ?><p class="empty">No open vacancies match your search.</p>
  <?php else: ?><div class="table-wrap"><table>
    <thead><tr><th>Job title</th><th>Department</th><th class="num">Positions</th><th>Closing date</th><?php if (is_hr()): ?><th class="num">Applicants</th><?php endif; ?><th></th></tr></thead>
    <tbody><?php foreach ($rows as $r):
        $days = (int)floor((strtotime($r['closing_date']) - strtotime(date('Y-m-d'))) / 86400); ?>
      <tr>
        <td><a href="vacancy_view.php?id=<?= (int)$r['id'] ?>"><strong><?= e($r['title']) ?></strong></a></td>
        <td><?= e($r['dept_name']) ?></td>
        <td class="num"><?= (int)$r['positions'] ?></td>
        <td><?= e(fmt_date($r['closing_date'])) ?>
          <?php if ($days < 0): ?><span class="badge b-closed">Closed</span><?php elseif ($days <= 3): ?><span class="badge b-pending">Closing soon</span><?php endif; ?></td>
        <?php if (is_hr()): ?><td class="num"><?= (int)$r['apps'] ?></td><?php endif; ?>
        <td><?php if ((int)$r['mine'] > 0): ?><span class="badge b-selected">Applied</span><?php else: ?><a class="btn ghost sm" href="vacancy_view.php?id=<?= (int)$r['id'] ?>">View details</a><?php endif; ?></td>
      </tr>
    <?php endforeach; ?></tbody>
  </table></div><?php endif; ?>
</section>
<?php include 'includes/footer.php'; ?>
