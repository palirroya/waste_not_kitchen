<?php

require_once("auth.php");
auth_init();
if (!$auth_is_logged_in && $_SESSION["user"]["role"] !== "in_need") {
    header("Location: login.php");
    exit();
}

require_once("database.php");
db_open();
$message = null;
$actions_disabled = false;
$user_id = $_SESSION["user"]["id"];

if (isset($_POST["confirm"])) {

    // Get all cart items
    $stmt = $db_conn->prepare("
        SELECT DonatedOrderClaims.id
        FROM Plates 
        JOIN Orders ON Orders.plate_id = Plates.id 
        JOIN DonatedOrders ON DonatedOrders.order_id = Orders.id 
		JOIN DonatedOrderClaims ON DonatedOrderClaims.donated_order_id = DonatedOrders.id
        WHERE DonatedOrderClaims.status = 'in_cart'
		AND DonatedOrderClaims.in_need_user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart = $stmt->get_result();

    while ($item = $cart->fetch_assoc()) {

        $stmt = $db_conn->prepare("
            UPDATE DonatedOrderClaims SET status='claimed'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $item["id"]);
        $stmt->execute();
    }

    $message = "Claim successful!";
}

if (isset($_POST["cancel"])) {

    // Get all cart items
    $stmt = $db_conn->prepare("
        SELECT DonatedOrders.id AS donated_order_id, DonatedOrderClaims.id AS order_claim_id,
        DonatedOrderClaims.quantity AS quantity
        FROM Plates 
        JOIN Orders ON Orders.plate_id = Plates.id 
        JOIN DonatedOrders ON DonatedOrders.order_id = Orders.id 
		JOIN DonatedOrderClaims ON DonatedOrderClaims.donated_order_id = DonatedOrders.id
        WHERE DonatedOrderClaims.status = 'in_cart'
		AND DonatedOrderClaims.in_need_user_id = ?
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart = $stmt->get_result();

    while ($item = $cart->fetch_assoc()) {

        // Restore donated plate quantity
        $stmt2 = $db_conn->prepare("
            UPDATE DonatedOrders 
            SET quantity_available = quantity_available + ? 
            WHERE id = ?
        ");
        $stmt2->bind_param("ii", $item["quantity"], $item["donated_order_id"]);
        $stmt2->execute();

        // Mark order as cancelled
        $stmt3 = $db_conn->prepare("
            UPDATE DonatedOrderClaims SET status='cancelled' 
            WHERE id = ?
        ");
        $stmt3->bind_param("i", $item["order_claim_id"]);
        $stmt3->execute();
    }

    $message = "Claim cancelled.";
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">

    <h1 class="mb-4">Checkout</h1>

    <a href="in_need_homepage.php" class="btn btn-dark mb-4">Back</a>

    <?php if (!empty($message)) { ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php } ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Reserved Donated Plates in Your Cart</div>
        <div class="card-body">
            <?php
            $plates = $db_conn->query("
                SELECT Plates.description, DonatedOrderClaims.quantity
                FROM Plates 
                JOIN Orders ON Orders.plate_id = Plates.id 
                JOIN DonatedOrders ON DonatedOrders.order_id = Orders.id 
			    JOIN DonatedOrderClaims ON DonatedOrderClaims.donated_order_id = DonatedOrders.id
                WHERE DonatedOrderClaims.status = 'in_cart'
		        AND DonatedOrderClaims.in_need_user_id = $user_id
            ");
            if ($plates->num_rows === 0) { 
                $actions_disabled = true; ?>
                <p>You do not have any plates.</p>
            <?php } else { ?>
                <table class="table table-bordered">
                    <tr>
                        <th>Plate</th>
                        <th>Quantity</th>
                    </tr>
                    <?php while ($p = $plates->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p["description"]); ?></td>
                        <td><?php echo intval($p["quantity"]); ?></td>
                    </tr>
            <?php }?>
                </table>
            <?php } ?>
        </div>
    </div>

    <form method="POST" class="d-flex gap-2">
        <button name="confirm" class="btn btn-success <?php if ($actions_disabled) echo "disabled" ?>">Confirm Claim</button>
        <button name="cancel" class="btn btn-danger <?php if ($actions_disabled) echo "disabled" ?>">Cancel Claim</button>
    </form>

</div>
</body>
</html>

<?php 
db_close();
?>
