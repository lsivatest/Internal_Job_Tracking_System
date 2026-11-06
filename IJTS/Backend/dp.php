<?php
$host="loclhost";
$database_name="internal_job_tracking_system";
$username="root";
$password="";//Default XAMPP setup only

try {
   $pdo=new POD(
     "mysql:host=$host;database_name;charset=utf8mb4",
     $username,
     $password
   );
   $pdo->setAttribute(
     POD::ATTR_ERRMODE,
     POD::ERRMODE_EXCEPTION
   );

} catch (PDOException $e) {
  http_response_code(500);
  echo ("Database Connection Failed");
}
?>