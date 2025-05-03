<?php
session_start();
session_unset();
session_destroy();
header("Location: ../page/auth_login.html");
exit();
?>