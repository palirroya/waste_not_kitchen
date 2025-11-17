<?php

define("DEFAULT_SQL", "default.sql");
require_once("database.php");
db_open();
$commands = file_get_contents(DEFAULT_SQL);   
echo "<h1>" . ($db_conn->multi_query($commands) ? "Success!" : "Failed.") . "</h1>";
db_close();

?>

<a href="index.php">Home</a>