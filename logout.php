<?php
session_start();
session_unset();
session_destroy();
header("Location: /medibook/index.php");
exit;
?>
