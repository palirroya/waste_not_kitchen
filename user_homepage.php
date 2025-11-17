<?php

require_once("auth.php");
auth_init();
if ($auth_is_logged_in == false) {
    header("Location: login.php");
    exit();
}

?>

<h1>Hello, <?php echo $_SESSION["user"]["name"] ?>!</h1>
<a href="logout.php">Log out</a>
<a href="index.php">Home</a>
<p>You are currently logged in. Your information is:</p>
<b><?php print_r($_SESSION["user"]) ?></b>