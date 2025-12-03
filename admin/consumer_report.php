<?php
// restaurant_report.php
// Usage: restaurant_report.php?id=<consumer_id>
// Requires: $db_conn to be an existing mysqli connection object

require_once("../database.php");
db_open();


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Missing or invalid id parameter.";
    exit;
}

$consumer_id = (int)$_GET['id'];

$stmt = $db_conn->prepare("
SELECT name, role
FROM Users U
WHERE U.id = ?
AND (U.role = 'customer' OR U.role = 'donor')");

$stmt->bind_param("i", $consumer_id);
$stmt->execute();
$stmt->bind_result($consumer_name, $consumer_role);
$stmt->fetch();
$stmt->close();

$now = new DateTime("now");
$start = (new DateTime("now"))->modify('-1 year');
$start_sql = $start->format('Y-m-d H:i:s');
$now_sql = $now->format('Y-m-d H:i:s');


// 1) Overall purchase cost within the past year
$cost = 0.0;
{
    $sql = "
        SELECT COALESCE(SUM(O.total_price), 0) AS cost
        FROM Orders O
        JOIN Plates P ON O.plate_id = P.id
        WHERE O.user_id = ? AND O.status = 'purchased'
        AND P.available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && ($row = $r->fetch_assoc())) {
        $cost = (float)$row['cost'];
    }
    if ($r) $r->free();
    $stmt->close();
}

// 2) Overall amount of plates purchased by consumer
$purchased_total = 0;
{
    $sql = "
    SELECT 
        (SUM(O.quantity)) AS purchased_total
    FROM Orders O
    JOIN Plates P ON O.plate_id = P.id
    WHERE O.user_id = ?
        AND O.status = 'purchased'
        AND P.available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && ($row = $r->fetch_assoc())) {
        $purchased_total = (int)$row['purchased_total'];
    }
    if ($r) $r->free();
    $stmt->close();
}

// Prepare monthly buckets
$months = [];         // i.e "2025-12"
$month_labels = [];   // i.e "Dec 2025"
$t0 = new DateTime("first day of this month");
$t0->modify("-11 months");
for ($i = 0; $i < 12; $i++) {
    $dt = clone $t0;
    $dt->modify("+$i months");
    $months[] = $dt->format('Y-m');
    $month_labels[] = $dt->format('M Y');
}

// 5) Monthly amount of plates bought for past 12 months
$monthly_purchased = array_fill(0, 12, 0);
{
        $sql = "
                SELECT COALESCE(SUM(O.quantity), 0) AS qty
                FROM Orders O
                JOIN Plates P ON O.plate_id = P.id
                WHERE O.user_id = ?
                AND O.status = 'purchased'
                AND P.available_from >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                AND P.available_from < DATE_SUB(NOW(), INTERVAL ?-1 MONTH)
            ";
        for ($i = 12; $i >= 0; $i--) {
            
            $stmt = $db_conn->prepare($sql);
            $stmt->bind_param("iii", $consumer_id, $i, $i);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && ($row = $r->fetch_assoc())) {
                $qty = (int)$row['qty'];
                $monthly_purchased[12 - $i] = $qty; 
            }
        }   
        if ($r) $r->free();
        $stmt->close();
    
}

// 6) Monthly cost per month and accumulated cost per month
$monthly_cost = array_fill(0, 12, 0.0);
$monthly_cost_cumulative = array_fill(0, 12, 0.0);
{
    $sql = "
        SELECT COALESCE(SUM(O.total_price), 0) AS rev
        FROM Orders O
        JOIN Plates P ON O.plate_id = P.id
        WHERE O.user_id = ?
        AND O.status = 'purchased'
        AND P.available_from >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        AND P.available_from < DATE_SUB(NOW(), INTERVAL ?-1 MONTH)
    ";

    for( $i = 12; $i >= 0; $i--) {
        $stmt = $db_conn->prepare($sql);
        $stmt->bind_param("iii", $consumer_id, $i, $i);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r && ($row = $r->fetch_assoc())) {
            $rev = (float)$row['rev'];
            $monthly_cost[12 - $i] = $rev;
        }
        if ($r) $r->free();
        $stmt->close();
    }

    // cumulative: running sum
    $running = 0.0;
    for ($i = 0; $i < 12; $i++) {
        $running += $monthly_cost[$i];
        $monthly_cost_cumulative[$i] = $running;
    }
}

// 8) Collapsible list of all orders placed within the past year
$orders = [];
{
    $sql = "
        SELECT O.id, P.description, O.quantity, O.total_price, P.available_from
        FROM Orders O
        JOIN Plates P ON O.plate_id = P.id
        WHERE O.user_id = ? AND P.available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ORDER BY P.available_from DESC
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $consumer_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $pid = (int)$row['id'];
            $orders[$pid] = [
                'id' => $pid,
                'description' => $row['description'],
                'quantity' => (int)$row['quantity'],
                'total_price' => (float)$row['total_price'],
                'available_from' => $row['available_from'],
            ];
        }
        $r->free();
    }
    $stmt->close();
}
// JSON encode arrays for charts
$chart_month_labels = json_encode($month_labels);
$chart_monthly_purchased = json_encode($monthly_purchased);
$chart_monthly_cost = json_encode($monthly_cost);
$chart_monthly_cost_cum = json_encode($monthly_cost_cumulative);

?>
<!doctype html>
<html lang="en">
          <div class="navbar">
        <a href="homepage.php">
            <img src="../images/wnk_logo.png" alt="Logo">
        </a>

        <a href="homepage.php"><-- BACK TO ADMIN HOME</a>
    </div>
<head>
  <meta charset="utf-8">
  <title><?= htmlspecialchars($consumer_name) ?> - <? echo date('Y'); ?> Report</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" >
  <style>

        /* TOP NAV */
        .navbar {
            display: flex;
            align-items: center;
            background-color: #6281ddff;
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
    body {}
    .stat-card { padding: 15px; border-radius: 8px; background: #f8f9fa; margin-bottom: 12px; }
    .stat-number { font-weight: 700; font-size: 1.5rem; }
    .chart-wrap { margin-top: 20px; }

        
  </style>

  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</head>
<body>
    
<div class="container">
  <h2><?= htmlspecialchars($consumer_name) ?> - <? echo date('Y'); ?> Report</h2>



  <div class="row gy-3">
    <div class="col-md-3">
      <div class="stat-card">
        <div>Total Purchase Cost</div>
        <div class="stat-number">$<?= number_format($cost, 2) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div>Total Plates Purchased</div>
        <div class="stat-number"><?= number_format($purchased_total) ?></div>
      </div>
    </div>
  </div>

  <div class="row chart-wrap">
    <div class="col-lg-6">
      <h5>Purchases Per Month</h5>
      <canvas id="barSold" width="400" height="260"></canvas>
    </div>
    <div class="col-lg-6">
      <h5>Purchase Cost per Month & Cumulative Cost</h5>
      <canvas id="lineRevenue" width="400" height="260"></canvas>
    </div>
  </div>

  <div class="row chart-wrap mt-4">
    <div class="col-md-6">
      <h5>Order Receipts</h5>
      <div class="accordion" id="platesAccordion">
        <?php if (empty($orders)): ?>
          <div class="alert alert-secondary">No orders purhcased in the past year.</div>
        <?php else: ?>
          <?php $idx = 0; foreach ($orders as $p): $idx++; ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?= $idx ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>" aria-expanded="false" aria-controls="collapse<?= $idx ?>">
                  <?= htmlspecialchars($p['description'] ?: "(no description)") ?>
                  &nbsp; | &nbsp; $<?= number_format($p['total_price'],2) ?>
                </button>
              </h2>
              <div id="collapse<?= $idx ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $idx ?>" data-bs-parent="#platesAccordion">
                <div class="accordion-body">
                  <div><strong>Purchased Near:</strong> <?= htmlspecialchars($p['available_from'])?></div>
                    <div><strong>Quantity:</strong> <?= number_format($p['quantity']) ?></div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <hr>

</div>

<!-- Bootstrap JS (for collapse) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const monthLabels = <?= $chart_month_labels ?>;
  const monthlySold = <?= $chart_monthly_purchased ?>;
  const monthlyCost = <?= $chart_monthly_cost ?>;
  const monthlyCostCum = <?= $chart_monthly_cost_cum ?>;

  // Bar chart: monthly purchases
  const ctxBar = document.getElementById('barSold').getContext('2d');
  new Chart(ctxBar, {
    type: 'bar',
    data: {
      labels: monthLabels,
      datasets: [{
        label: 'Plates sold',
        data: monthlySold,
      }]
    },
    options: {
      indexAxis: 'x',
      scales: {
        x: { title: { display: true, text: 'Month' } },
        y: { title: { display: true, text: 'Plates Purchased (Amt.)' }, beginAtZero: true }
      },
      plugins: { legend: { display: false } }
    }
  });

  // Line chart: purchase cost per month and cumulative cost
  const ctxLine = document.getElementById('lineRevenue').getContext('2d');
  new Chart(ctxLine, {
    type: 'line',
    data: {
      labels: monthLabels,
      datasets: [
        {
          label: 'Cost this month ($)',
          data: monthlyCost,
          tension: 0.3,
          fill: false,
          yAxisID: 'y'
        },
        {
          label: 'Accumulated Cost ($)',
          data: monthlyCostCum,
          tension: 0.3,
          fill: false,
          borderDash: [5,5],
          yAxisID: 'y'
        }
      ]
    },
    options: {
      scales: {
        x: { title: { display: true, text: 'Month' } },
        y: { title: { display: true, text: 'Cost ($)' }, beginAtZero: true }
      }
    }
  });

 
</script>
</body>
</html>