<?php
require_once("../auth.php");
require_once("../database.php");

/*
$restid: restaurant ID
$restaurant_name: restaurant name
$plate_count: # of plates created
$total_sales: total $ revenue
$order_count: # of plates sold
*/

db_open();
$restid = $_GET["id"] ?? null;
if (empty($restid)) 
{
    die("Error: No restaurant ID selected");
}

//fetching restaurant info
$stmt = $db_conn->prepare("
SELECT name
FROM Users U
WHERE U.id = ?
AND U.role = 'restaurant'");

$stmt->bind_param("i", $restid);
$stmt->execute();
$stmt->bind_result($restaurant_name);
$stmt->fetch();
$stmt->close();

if(empty($restaurant_name)) {
    die("Error: Restaurant not found");
}

///-----------------------
//GET # OF PLATES CREATED IN PAST YEAR
//------------------------
$stmt = $db_conn->prepare("
SELECT COUNT(*) 
FROM Plates 
WHERE owner_id = ?
AND available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
");
$stmt->bind_param("i", $restid);
$stmt->execute();
$stmt->bind_result($plate_count);
$stmt->fetch();
$stmt->close();

//-----------------------
//GET TOTAL SALES FOR RESTAURANT
//------------------------
$stmt = $db_conn->prepare("
    SELECT COUNT(*), COALESCE(SUM(total_price), 0)
    FROM Orders 
    WHERE plate_id IN (SELECT id FROM Plates WHERE owner_id = ?)
    AND status = 'purchased'
");
$stmt->bind_param("i", $restid);
$stmt->execute();
$stmt->bind_result($order_count, $total_sales);
$stmt->fetch();
$stmt->close();

//-------------------------------------------
//GET RECENTLY SOLD PLATES
//-------------------------------------------
$stmt = $db_conn->prepare("
    SELECT P.description, O.quantity, O.total_price, U.name, U.id
    FROM Orders O, Plates P, Users U
    WHERE O.plate_id = P.id
    AND P.owner_id = ?
    AND U.id = ?
    AND O.status = 'purchased'
    AND P.available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ORDER BY o.id DESC
");
$stmt->bind_param("ii", $restid, $restid);
$stmt->execute();
$stmt->bind_result($desc, $qty, $price, $name, $uid);

/*THIS NEEDS TO ME MOVED SOMEWHERE IN HTML
if(empty($desc)) {
    echo "<div class='metric-box'>
        <h3>Recent Sales:</h3>
        <p>No recent sales.</p>
    </div>";
    $stmt->close();
    exit();
}

echo "<div class='metric-box'>
        <h3>Recent Sales:</h3>";

while ($stmt->fetch()) {
    echo "<p><strong>$desc</strong> — $qty purchased by $name (ID: $uid) — $$price</p>";
}*/


echo "</div>";

$stmt->close();
// ^


//-----------------------------------------
//GET MONTHLY SALES AND ORDERS DATA FOR CHARTING
//-----------------------------------------
$stmt = $db_conn->prepare("
SELECT DATE_FORMAT(P.available_from, '%Y-%m') AS month, 
       COALESCE(SUM(O.total_price), 0) AS monthly_revenue,
       COUNT(O.id) AS monthly_orders
       FROM Plates P
       JOIN Orders O ON P.owner_id = O.plate_id
       WHERE P.owner_id = ?
       AND O.status = 'purchased'
       AND P.available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
       GROUP BY month
       ORDER BY month ASC
");
$stmt->bind_param("i", $restid);
$stmt->execute();
$stmt->bind_result($month, $monthly_revenue, $monthly_orders);
$stmt->fetch();
$stmt->close();


// -------------------------------------------
// HTML OUTPUT
// -------------------------------------------
?>

<!DOCTYPE html>
<html>
<head>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const monthly_orders_json = <?php echo json_encode($monthly_orders); ?>;
        const monthly_revenue_json = <?php echo json_encode($monthly_revenue); ?>;
        const total_sales_json = <?php echo $total_sales; ?>;
        //const plates_sold_json = <?php echo $plates_sold; ?>;
        //const plates_unsold_json = <?php echo $plates_unsold; ?>;
    </script>

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

    <!-- Total sales metric -->
    <div class="metric-box">
        <h2>Total Sales This Year:</h2>
        <h1>$<?php echo $total_sales; ?></h1>
    </div>

    <!-- Bar Chart --> 
    <div class="metric-box">
        <h3>Monthly Plate Sales</h3>
        <canvas id="salesBarChart"></canvas>
    </div>

    <!-- Revenue Line Chart -->
    <div class="metric-box">
        <h3>Monthly vs Cumulative Revenue</h3>
        <canvas id="revenueLineChart"></canvas>
    </div>

    <!-- Pie Chart -->
    <div class="metric-box">
        <h3>Plates Sold vs Unsold</h3>
        <canvas id="pieChart"></canvas>
    </div>

    <!-- Collapsible Sold Plates -->
    <div class="collapsible">View Sold Plates</div>
    <div class="content">
        <?php foreach ($soldPlatesList as $plate): ?>
            <p><?= $plate['description'] ?> — $<?= $plate['total_price'] ?></p>
        <?php endforeach; ?>
    </div>

    <!-- Collapsible Created Plates -->
    <div class="collapsible">View Created Plates</div>
    <div class="content">
        <?php foreach ($createdPlatesList as $plate): ?>
            <p><?= $plate['description'] ?> — Added: <?= $plate['available_from'] ?></p>
        <?php endforeach; ?>
    </div>

</div>

<!-- BAR CHART FOR MONTHLY SALES -->
<script>
new Chart(document.getElementById("salesBarChart"), {
    type: "bar",
    data: {
        labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
        datasets: [{
            label: "Plates Sold",
            data: monthly_revenue_json
        }]
    }
});
</script>

<!-- LINE CHART FOR MONTHLY VS CUMULATIVE REVENUE -->
<script>
let cumulative = [];
let sum = 0;
monthly_revenue_json.forEach(v => { sum += v; cumulative.push(sum); });

new Chart(document.getElementById("revenueLineChart"), {
    type: "line",
    data: {
        labels: ["Jan","Feb","Mar","Apr","May","Jun","Jul","Aug","Sep","Oct","Nov","Dec"],
        datasets: [
            {
                label: "Monthly Revenue",
                data: monthly_revenue_json,
                borderColor: "blue",
                fill: false
            },
            {
                label: "Cumulative Revenue",
                data: cumulative,
                borderColor: "green",
                fill: false
            }
        ]
    }
});
</script>

</body>
</html>