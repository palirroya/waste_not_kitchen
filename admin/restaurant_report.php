<?php
// restaurant_report.php
// Usage: restaurant_report.php?id=<restaurant_id>
// Requires: $db_conn to be an existing mysqli connection object

require_once("../database.php");
db_open();


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Missing or invalid id parameter.";
    exit;
}

$restaurant_id = (int)$_GET['id'];

$stmt = $db_conn->prepare("
SELECT name
FROM Users U
WHERE U.id = ?
AND U.role = 'restaurant'");

$stmt->bind_param("i", $restaurant_id);
$stmt->execute();
$stmt->bind_result($restaurant_name);
$stmt->fetch();
$stmt->close();

$now = new DateTime("now");
$start = (new DateTime("now"))->modify('-1 year');
$start_sql = $start->format('Y-m-d H:i:s');
$now_sql = $now->format('Y-m-d H:i:s');

// 1) Overall revenue within the past year 
{
    $sql = "
        SELECT COALESCE(SUM(o.total_price), 0) AS revenue
        FROM Orders o
        JOIN Plates p ON o.plate_id = p.id
        WHERE p.owner_id = ? AND o.status = 'purchased'
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $restaurant_id);
    
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && ($row = $r->fetch_assoc())) {
        $revenue = (float)$row['revenue'];
    }
    if ($r) $r->free();
    $stmt->close();
}

// 2) Overall amount of plates created by the restaurant (sum of quantity_available for Plates created in past year)
$total_created_plates = 0;
{
    $sql = "
    SELECT 
        (SUM(P.quantity_available) + SUM(O.quantity)) AS created_total
    FROM Plates P
    LEFT JOIN Orders O ON P.id = O.plate_id
        AND O.status = 'purchased'
    WHERE P.owner_id = ?
        AND P.available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $restaurant_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && ($row = $r->fetch_assoc())) {
        $total_created_plates = (int)$row['created_total'];
    }
    if ($r) $r->free();
    $stmt->close();
}

// 3) Amount of plates sold by the restaurant (sum of Orders.quantity for purchased orders, within past year)
$total_plates_sold = 0;
{
    $sql = "
        SELECT COALESCE(SUM(o.quantity),0) AS sold_total
        FROM Orders o
        JOIN Plates p ON o.plate_id = p.id
        WHERE p.owner_id = ? AND o.status = 'purchased'
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $restaurant_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && ($row = $r->fetch_assoc())) {
        $total_plates_sold = (int)$row['sold_total'];
    }
    if ($r) $r->free();
    $stmt->close();
}

// 4) Amount of plates not sold = total_created_plates - total_plates_sold (min 0)
$total_plates_unsold = $total_created_plates - $total_plates_sold;
if ($total_plates_unsold < 0) $total_plates_unsold = 0;

// Prepare monthly buckets (most recent month first)
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

// 5) Monthly amount of plates sold for past 12 months
$monthly_sold = array_fill(0, 12, 0);
{
        $sql = "
                SELECT COALESCE(SUM(O.quantity), 0) AS qty
                FROM Orders O
                JOIN Plates P ON O.plate_id = P.id
                WHERE P.owner_id = ? 
                AND O.status = 'purchased'
                AND P.available_from >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                AND P.available_from < DATE_SUB(NOW(), INTERVAL ?-1 MONTH)

            ";
        for ($i = 12; $i >= 0; $i--) {
            
            $stmt = $db_conn->prepare($sql);
            $stmt->bind_param("iii", $restaurant_id, $i, $i);
            $stmt->execute();
            $r = $stmt->get_result();
            if ($r && ($row = $r->fetch_assoc())) {
                $qty = (int)$row['qty'];
                $monthly_sold[12 - $i] = $qty; 
            }
        }   
        if ($r) $r->free();
        $stmt->close();
    
}

// 6) Monthly revenue per month and accumulated revenue per month (most recent -> oldest)
$monthly_revenue = array_fill(0, 12, 0.0);
$monthly_revenue_cumulative = array_fill(0, 12, 0.0);
{
    $sql = "
        SELECT COALESCE(SUM(O.total_price), 0) AS rev
        FROM Orders O
        JOIN Plates P ON O.plate_id = P.id
        WHERE P.owner_id = ? 
        AND O.status = 'purchased'
        AND P.available_from >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        AND P.available_from < DATE_SUB(NOW(), INTERVAL ?-1 MONTH)
    ";

    for( $i = 12; $i >= 0; $i--) {
        $stmt = $db_conn->prepare($sql);
        $stmt->bind_param("iii", $restaurant_id, $i, $i);
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r && ($row = $r->fetch_assoc())) {
            $rev = (float)$row['rev'];
            $monthly_revenue[12 - $i] = $rev;
        }
        if ($r) $r->free();
        $stmt->close();
    }

    // cumulative: running sum from index 0 -> N (most recent to older)
    $running = 0.0;
    for ($i = 0; $i < 12; $i++) {
        $running += $monthly_revenue[$i];
        $monthly_revenue_cumulative[$i] = $running;
    }
}

// 7) SOLD VS TOTAL-SOLD PIE CHART
$pie_sold = $total_plates_sold;
$pie_unsold = $total_plates_unsold;

// 8) Collapsible list of all created plates within the past year, newest -> oldest, with SOLD and UNSOLD counters (per plate)
$plates = [];
{
    $sql = "
        SELECT id, description, quantity_available, price, available_from, available_to
        FROM Plates
        WHERE owner_id = ? AND available_from BETWEEN ? AND ?
        ORDER BY available_from DESC
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("iss", $restaurant_id, $start_sql, $now_sql);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $pid = (int)$row['id'];
            $plates[$pid] = [
                'id' => $pid,
                'description' => $row['description'],
                'quantity_available' => (int)$row['quantity_available'],
                'price' => (float)$row['price'],
                'available_from' => $row['available_from'],
                'available_to' => $row['available_to'],
                'sold' => 0,
                'unsold' => 0
            ];
        }
        $r->free();
    }
    $stmt->close();

    if (!empty($plates)) {
        // fetch sold counts per plate
        $plateIds = array_keys($plates);
        $sql = "
            SELECT O.plate_id, COALESCE(SUM(O.quantity),0) as sold_qty
            FROM Orders O
            WHERE O.plate_id IN (" . implode(',', array_map('intval', $plateIds)) . ") AND O.status = 'purchased'
        ";
        $sql .= " GROUP BY O.plate_id ";
        // prepare dynamic statement:
        $stmt = $db_conn->prepare($sql);
        
        $stmt->execute();
        $r = $stmt->get_result();
        if ($r) {
            while ($row = $r->fetch_assoc()) {
                $pid = (int)$row['plate_id'];
                $sold_qty = (int)$row['sold_qty'];
                if (isset($plates[$pid])) {
                    $plates[$pid]['sold'] = $sold_qty;
                    $unsold = $plates[$pid]['quantity_available'] - $sold_qty;
                    $plates[$pid]['unsold'] = ($unsold < 0) ? 0 : $unsold;
                }
            }
            $r->free();
        }
        $stmt->close();
    }
}

// JSON encode arrays for charts
$chart_month_labels = json_encode($month_labels);
$chart_monthly_sold = json_encode($monthly_sold);
$chart_monthly_revenue = json_encode($monthly_revenue);
$chart_monthly_revenue_cum = json_encode($monthly_revenue_cumulative);
$chart_pie = json_encode([$pie_sold, $pie_unsold]);

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
  <title><?= htmlspecialchars($restaurant_name) ?> - <? echo date('Y'); ?> Report</title>
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
  <h2><?= htmlspecialchars($restaurant_name) ?> - <? echo date('Y'); ?> Report</h2>



  <div class="row gy-3">
    <div class="col-md-3">
      <div class="stat-card">
        <div>Overall revenue (past year)</div>
        <div class="stat-number">$<?= number_format($revenue, 2) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div>Total plates created (past year)</div>
        <div class="stat-number"><?= number_format($total_created_plates) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div>Total plates sold (past year)</div>
        <div class="stat-number"><?= number_format($total_plates_sold) ?></div>
      </div>
    </div>
    <div class="col-md-3">
      <div class="stat-card">
        <div>Total plates unsold (past year)</div>
        <div class="stat-number"><?= number_format($total_plates_unsold) ?></div>
      </div>
    </div>
  </div>

  <div class="row chart-wrap">
    <div class="col-lg-6">
      <h5>Sales per Month</h5>
      <canvas id="barSold" width="400" height="260"></canvas>
    </div>
    <div class="col-lg-6">
      <h5>Revenue per Month & Accumulated Revenue</h5>
      <canvas id="lineRevenue" width="400" height="260"></canvas>
    </div>
  </div>

  <div class="row chart-wrap mt-4">
    <div class="col-md-6">
      <h5>Plate Sales Ratio</h5>
      <canvas id="pieSoldUnsold" width="300" height="220"></canvas>
    </div>
    <div class="col-md-6">
      <h5>Plates Created</h5>
      <div class="accordion" id="platesAccordion">
        <?php if (empty($plates)): ?>
          <div class="alert alert-secondary">No plates created in the past year.</div>
        <?php else: ?>
          <?php $idx = 0; foreach ($plates as $p): $idx++; ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?= $idx ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>" aria-expanded="false" aria-controls="collapse<?= $idx ?>">
                  <?= htmlspecialchars($p['description'] ?: "(no description)") ?>
                  &nbsp; | &nbsp; $<?= number_format($p['price'],2) ?>
                </button>
              </h2>
              <div id="collapse<?= $idx ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $idx ?>" data-bs-parent="#platesAccordion">
                <div class="accordion-body">
                  <div><strong>From:</strong> <?= htmlspecialchars($p['available_from']) ?> &nbsp; <strong>To:</strong> <?= htmlspecialchars($p['available_to']) ?></div>
                  <div><strong>SOLD:</strong> <?= number_format($p['sold']) ?> &nbsp; <strong>UNSOLD:</strong> <?= number_format($p['quantity_available']) ?></div>
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
  const monthLabels = <?= $chart_month_labels ?>; // most recent -> oldest
  const monthlySold = <?= $chart_monthly_sold ?>;
  const monthlyRevenue = <?= $chart_monthly_revenue ?>;
  const monthlyRevenueCum = <?= $chart_monthly_revenue_cum ?>;
  const pieData = <?= $chart_pie ?>;

  // MONTHLY PLATE SALES BAR CHART
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
        y: { title: { display: true, text: 'Plates Sold (Amt.)' }, beginAtZero: true }
      },
      plugins: { legend: { display: false } }
    }
  });

  // Line chart: revenue per month and cumulative
  const ctxLine = document.getElementById('lineRevenue').getContext('2d');
  new Chart(ctxLine, {
    type: 'line',
    data: {
      labels: monthLabels,
      datasets: [
        {
          label: 'Revenue this month ($)',
          data: monthlyRevenue,
          tension: 0.3,
          fill: false,
          yAxisID: 'y'
        },
        {
          label: 'Accumulated revenue ($)',
          data: monthlyRevenueCum,
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
        y: { title: { display: true, text: 'Revenue ($)' }, beginAtZero: true }
      }
    }
  });

  // Pie chart: sold vs unsold
  const ctxPie = document.getElementById('pieSoldUnsold').getContext('2d');
  new Chart(ctxPie, {
    type: 'pie',
    data: {
      labels: ['Sold', 'Unsold'],
      datasets: [{
        data: pieData
      }]
    },
    options: {}
  });
</script>
</body>
</html>