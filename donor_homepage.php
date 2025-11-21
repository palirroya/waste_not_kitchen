<?php

require_once("auth.php");
auth_init();
if (!$auth_is_logged_in || $_SESSION["user"]["role"] != "donor") {
    header("Location: login.php");
    exit();
}

?>