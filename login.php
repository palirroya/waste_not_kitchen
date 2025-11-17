<?php

require_once("auth.php");
auth_init();
if ($auth_is_logged_in) {
    header("Location: user_homepage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = hash("sha256", $_POST["password"]);
    require_once("database.php");
    db_open();
    $query = "SELECT * FROM Users WHERE username=? AND password_hash=?";
    $statement = $db_conn->prepare($query);
    $statement->bind_param("ss", $username, $password);
    $statement->execute();
    $result = $statement->get_result();
    if ($result->num_rows == 1) {
        $userData = $result->fetch_all(MYSQLI_ASSOC)[0];
        auth_log_in($userData);
        db_close();
        header("Location: user_homepage.php");
    } else {
        db_close();
        header("Location: login.php");
    }
}

?>

<h1>Log in</h1>
<a href="index.php">Home</a>

<form action="login.php" method="POST">
    <input type="string" placeholder="Username" name="username"></input>
    <input type="password" paceholder="Password" name="password"></input>
    <input type="submit" />
</form>