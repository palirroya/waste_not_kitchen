<?php

define("DB_HOST", "localhost");
define("DB_USERNAME", "root");
define("DB_PASSWORD", "root");
define("DB_NAME", "wnk");

$db_conn = null;

function db_open() {
    global $db_conn;
    $db_conn = mysqli_connect(DB_HOST, DB_USERNAME, DB_PASSWORD, DB_NAME);
    if (!$db_conn)
    die("Connection to database failed: " . mysqli_connect_error());
}

function db_close() {
    global $db_conn;
    mysqli_close($db_conn);
    $db_conn = null;
}

?>