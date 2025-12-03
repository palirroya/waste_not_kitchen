<?php

require_once("../database.php");

db_open();
$restid = $_GET["id"] ?? null;
if (empty($restid)) 
{
    die("Error: No ID selected");
}

//fetching info
$stmt = $db_conn->prepare("
SELECT name
FROM Users U
WHERE U.id = ?
AND U.role = 'customer' OR U.role = 'donor'");

$stmt->bind_param("i", $restid);
$stmt->execute();
$stmt->bind_result($name);
$stmt->fetch();
$stmt->close();

if(empty($name)) {
    die("Error: Customer or donor not found");
}

$stmt = $db_conn->prepare("
    SELECT COUNT(*), COALESCE(SUM(total_price), 0)
    FROM Orders 
    WHERE customer_id = ?
    AND status = 'purchased'
");
$stmt->bind_param("i", $restid);
$stmt->execute();
$stmt->bind_result($purchase_count, $total_cost);
$stmt->fetch();
$stmt->close();


?>

<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>



    <style>
    .dashboard {
        width: 90%;
        margin: auto;
    }

    .metric-box {
        background: white;
        padding: 20px;
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }

    .collapsible {
        background: #eee;
        padding: 12px;
        cursor: pointer;
        border-radius: 10px;
        margin: 10px 0;
    }

    .content {
        display: none;
        padding: 10px;
        border-left: 4px solid #ccc;
    }
    </style>
</head>
<body>

<h1><?php echo $restaurant_name; ?> - Yearly Report</h1>

<!--DASHBOARD LAYOUT-->

<div class="dashboard">

    <!-- TOTAL SALES -->
    <div class="metric-box">
        <h2>Total Purchase Amount This Year:</h2>
        <h1>$<?php echo $total_cost; ?></h1>
    </div>

    <!-- MONTHLY SALES --> 
    <div class="metric-box">
        <h3>Total Plates Purchased:</h3>
        <h1><?php echo $purchase_count; ?></h1>
    </div>


</div>

</body>
</html>