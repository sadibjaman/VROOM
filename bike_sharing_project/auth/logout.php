<?php
// File: auth/logout.php
session_start();
session_destroy();
header("Location: /bike_sharing_project/");
exit();
?>