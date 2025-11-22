<?php
// index.php
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Waste Not Kitchen</title>

    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #E3D2A2;
            color: #333;
            text-align: center;
        }

        /* NAVBAR */
        .navbar {
            display: flex;
            align-items: center;
            background-color: #D1C28F;
            padding: 10px 20px;
            font-size: 20px;
        }

        .navbar a {
            margin-left: 20px;
            text-decoration: none;
            color: black;
            font-weight: bold;
        }

        .navbar a:hover {
            text-decoration: underline;
        }

        .logo {
            height: 60px;
            margin-right: auto;
        }

        /* FOOD IMAGES STRIP */
        .food-strip {
            width: 100%;
            overflow: hidden;
        }

        .food-strip img {
            width: 100%;
            height: auto;
            display: block;
        }
        /* MAIN CONTENT */
        .content {
            padding: 40px 20px;
            max-width: 900px;
            margin: auto;
            font-size: 22px;
            line-height: 1.7;
        }

        .title {
            font-size: 48px;
            font-weight: bold;
            margin-top: 20px;
        }

        .subtitle {
            font-size: 24px;
            margin-top: 5px;
            margin-bottom: 40px;
        }
    </style>
</head>
<body>

    <!-- NAVBAR -->
    <div class="navbar">
        <a href="index.php">
            <img src="images/wnk_logo.png" alt="Logo" class="logo">
        </a>
        <a href="index.php">HOME</a>
        <a href="login.php">LOG IN</a>
        <a href="user_homepage.php">USER HOMEPAGE</a>
        <a href="reset_database.php">RESET DATABASE</a>
    </div>

    <!-- FOOD STRIP -->
    <div class="food-strip">
        <img src="images/food_strip.png" alt="Food Strip">
    </div>

    <!-- CONTENT -->
    <div class="content">
        <div class="title">WASTE NOT KITCHEN</div>
        <div class="subtitle">Ordering Food for Sustainability.</div>

        <p>
            We are a website that allows kitchens to post discounted meals to help fight food loss. 
            Food loss is the waste of readily consumable or purchasable food for no reason other than 
            it was not consumed in time.
        </p>

        <p>
            Made for Database Management Systems under Dr. Hua.
        </p>
    </div>

</body>
</html>


<!--<h1>Waste Not Kitchen</h1>

<a href="login.php">Log in</a>
<a href="user_homepage.php">User Homepage</a>
<a href="reset_database.php">Reset database</a>-->