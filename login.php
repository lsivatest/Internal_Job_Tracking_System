<?php
require_once 'includes/functions.php';
if (current_user()) { redirect('dashboard.php'); }
$error = '';
$email = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $pass = $_POST['password'] ?? '';
    $row = q_one("SELECT * FROM users WHERE email = ? AND status = 'Active'", [$email]);
    if ($row && password_verify($pass, $row['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['uid'] = (int)$row['id'];
        log_action('Login', 'User signed in', (int)$row['id']);
        redirect('dashboard.php');
    }
    $error = 'The email or password is incorrect.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in | <?= e(APP_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700&family=Source+Sans+3:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
<div class="login">
  <section class="login-art">
    <div>
      <div class="brand" style="padding:0 0 18px"><span class="mark" aria-hidden="true"><i></i><i></i><i></i></span><span class="brand-text"><strong><?= e(APP_NAME) ?></strong></span></div>
      <h1>Every vacancy, from request to hire.</h1>
    </div>
    <p>Departments raise vacancies, Human Resources publishes them, and everyone can see where each application stands.</p>
    <ol class="tracker" aria-hidden="true">
      <li class="done"><span class="dot"></span><span class="lbl">Received</span></li>
      <li class="done"><span class="dot"></span><span class="lbl">Shortlisted</span></li>
      <li class="current"><span class="dot"></span><span class="lbl">Interview</span></li>
      <li><span class="dot"></span><span class="lbl">Decision</span></li>
    </ol>
  </section>
  <section class="login-form">
    <div class="login-box">
      <h2>Sign in</h2>
      <p class="sub">Use your company email and password.</p>
      <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <div class="field"><label for="email">Email</label><input type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus></div>
        <div class="field"><label for="password">Password</label><input type="password" id="password" name="password" required></div>
        <button class="btn" type="submit" style="width:100%">Sign in</button>
      </form>
    </div>
  </section>
</div>
</body>
</html>
