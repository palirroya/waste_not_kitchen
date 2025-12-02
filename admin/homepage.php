

<?php
// admin_homepage.php
require_once("../auth.php");
auth_init();
if (!$auth_is_logged_in || $_SESSION["user"]["role"] != "admin") {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Page</title>

    <style>
        body {
            margin: 0;
            background-color: #ffffff;
            font-family: Arial, sans-serif;
            text-align: center;
        }

        /* TOP NAV */
        .navbar {
            display: flex;
            align-items: center;
            background-color: #87dd62ff;
            padding: 10px 20px;
            border-bottom: 2px solid #292929ff;
        }

        .navbar img {
            height: 45px;
            margin-right: 20px;
            cursor: pointer;
        }

        .navbar a {
            margin-right: 25px;
            text-decoration: none;
            color: black;
            font-size: 18px;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        /* MAIN TITLE */
        h1 {
            margin-top: 40px;
            font-size: 42px;
            color: #000;
        }

        /* REPORT LINKS */
        .report-link {
            display: block;
            margin-top: 25px;
            font-size: 20px;
            color: black;
            text-decoration: none;
        }

        .report-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <a href="../index.php">
            <img src="../images/wnk_logo.png" alt="Logo">
        </a>

        <a href="index.php"><-- BACK TO HOME</a>
    </div>

    <!-- PAGE TITLE -->
    <h1>ADMIN PAGE</h1>

    <!-- REPORT LINKS -->
    <a class="report-link" href="search_restaurant.php">RESTAURANT ACTIVITY REPORTS</a>
    <a class="report-link" href="#">MEAL PURCHASE REPORTS</a>
    <a class="report-link" href="#">NEEDY RECEIPT REPORTS</a>
    <a class="report-link" href="#">DONOR DONATION REPORTS</a>

</body>
</html>

<!--



-->