<?php
require_once"dp.php";

echo "Database Comnnection Sucessfully";
$start=$pdo->query ("Select Count(*) from departments");
$count=$start->fetchColumn();

echo "<br>" User Count".$count;