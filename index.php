<?php
require_once 'includes/functions.php';
redirect(current_user() ? 'dashboard.php' : 'login.php');
