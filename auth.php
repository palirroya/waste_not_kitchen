<?php

$auth_is_logged_in = false;

function auth_init() {
    global $auth_is_logged_in;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $auth_is_logged_in = isset($_SESSION["user"]);
}

function auth_log_in($userData) {
    global $auth_is_logged_in;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION["user"] = $userData;
    $auth_is_logged_in = true;
}

function auth_log_out() {
    global $auth_is_logged_in;
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    $_SESSION = [];
    session_destroy();
    $auth_is_logged_in = false;
}

?>