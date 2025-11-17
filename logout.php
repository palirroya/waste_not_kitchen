<?php

require_once("auth.php");
auth_init();
auth_log_out();
header("Location: index.php");

?>