<?php
// restaurant_report.php
// Usage: restaurant_report.php?id=<needy_id>
// Requires: $db_conn to be an existing mysqli connection object

require_once("../database.php");
db_open();


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo "Missing or invalid id parameter.";
    exit;
}

$needy_id = (int)$_GET['id'];

$stmt = $db_conn->prepare("
SELECT name, role
FROM Users U
WHERE U.id = ?
AND (U.role = 'in_need')");

$stmt->bind_param("i", $needy_id);
$stmt->execute();
$stmt->bind_result($needy_name, $needy_role);
$stmt->fetch();
$stmt->close();

$now = new DateTime("now");
$start = (new DateTime("now"))->modify('-1 year');
$start_sql = $start->format('Y-m-d H:i:s');
$now_sql = $now->format('Y-m-d H:i:s');


// 2) Overall amount of plates picked up by needy
$purchased_total = 0;
{
    $sql = "
    SELECT 
        (SUM(O.quantity)) AS purchased_total
    FROM DonatedOrderClaims O
    WHERE O.in_need_user_id = ?
    AND O.status = 'claimed'
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $needy_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r && ($row = $r->fetch_assoc())) {
        $purchased_total = (int)$row['purchased_total'];
    }
    if ($r) $r->free();
    $stmt->close();
}


// 8) Collapsible list of all orders picked up within the past year
$orders = [];
{
    $sql = "
        SELECT O.id, P.description, O.quantity, P.available_from
        FROM DonatedOrderClaims O
        JOIN DonatedOrders D ON O.donated_order_id = D.id
        JOIN Orders OO ON D.order_id = OO.id
        JOIN Plates P ON OO.plate_id = P.id
        WHERE O.in_need_user_id = ? 
        AND O.status = 'claimed' 
        AND P.available_from >= DATE_SUB(NOW(), INTERVAL 1 YEAR)
        ORDER BY P.available_from DESC
    ";
    $stmt = $db_conn->prepare($sql);
    $stmt->bind_param("i", $needy_id);
    $stmt->execute();
    $r = $stmt->get_result();
    if ($r) {
        while ($row = $r->fetch_assoc()) {
            $pid = (int)$row['id'];
            $orders[$pid] = [
                'id' => $pid,
                'description' => $row['description'],
                'quantity' => (int)$row['quantity'],
                'available_from' => $row['available_from'],
            ];
        }
        $r->free();
    }
    $stmt->close();

}

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
  <title><?= htmlspecialchars($needy_name) ?> - <? echo date('Y'); ?> Report</title>
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
  <h2><?= htmlspecialchars($needy_name) ?> - <? echo date('Y'); ?> Report</h2>



  <div class="row gy-3">
    <div class="col-md-3">
      <div class="stat-card">
        <div>Total Plates Picked Up</div>
        <div class="stat-number"><?= number_format($purchased_total) ?></div>
      </div>
    </div>
  </div>

  
  <div class="row chart-wrap mt-4">
    <div class="col-md-6">
      <h5>Pickup Receipts</h5>
      <div class="accordion" id="platesAccordion">
        <?php if (empty($orders)): ?>
          <div class="alert alert-secondary">No orders picked up in the past year.</div>
        <?php else: ?>
          <?php $idx = 0; foreach ($orders as $p): $idx++; ?>
            <div class="accordion-item">
              <h2 class="accordion-header" id="heading<?= $idx ?>">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapse<?= $idx ?>" aria-expanded="false" aria-controls="collapse<?= $idx ?>">
                  <?= htmlspecialchars($p['description'] ?: "(no description)") ?>
                  &nbsp;
                </button>
              </h2>
              <div id="collapse<?= $idx ?>" class="accordion-collapse collapse" aria-labelledby="heading<?= $idx ?>" data-bs-parent="#platesAccordion">
                <div class="accordion-body">
                  <div><strong>Picked up Near:</strong> <?= htmlspecialchars($p['available_from'])?></div>
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
  const pieData = <?= $chart_pie ?>;



 
</script>
</body>
</html>