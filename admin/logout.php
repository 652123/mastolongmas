<?php
session_start();
session_destroy();
header("Location: ../index.php?modal=login");
exit;
?>

