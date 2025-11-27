<?php
session_start();
require_once("auth.php");
require_once("database.php");

auth_init();

if (!$auth_is_logged_in || $_SESSION["user"]["role"] !== "restaurant") {
    header("Location: login.php");
    exit();
}

if (isset($_GET['id'])) {
    $plate_id = intval($_GET['id']);
    $owner_id = $_SESSION['user']['id'];

    db_open();
    global $db_conn;

    $sql = "DELETE FROM Plates WHERE id = ? AND owner_id = ?";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("ii", $plate_id, $owner_id);

    if ($stmt->execute()) {
        // double check deletion
        if ($stmt->affected_rows > 0) {
            $message = "Plate deleted successfully.";
        } else {
            $message = "Error: Could not delete plate (it may not exist or you do not have permission).";
        }
    } else {
        $message = "Database error: " . $stmt->error;
    }

    $stmt->close();
    db_close();
}

header("Location: restaurant_homepage.php");
exit();
?>