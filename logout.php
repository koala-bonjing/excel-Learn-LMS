<?php
session_start();
session_destroy();

// Clear remember-me cookie
setcookie("excelearn_rem_user", "", time() - 3600, "/");

header("Location: index.php");
exit();