<?php
session_start();
require_once("auth.php");
auth_init();

if (!$auth_is_logged_in && $_SESSION["user"]["role"] !== "donor") {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user"]["id"];
require_once("database.php");
db_open();
$message = null;
$actions_disabled = false;

if (isset($_POST["confirm"])) {

    // Get all cart items
    $stmt = $db_conn->prepare("
        SELECT id, plate_id, quantity
        FROM Orders
        WHERE user_id = ? AND status='in_cart'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart = $stmt->get_result();

    while ($item = $cart->fetch_assoc()) {

        // Mark order as purchased
        $stmt = $db_conn->prepare("
            UPDATE Orders SET status='purchased'
            WHERE id = ?
        ");
        $stmt->bind_param("i", $item["id"]);
        $stmt->execute();

         // Create a donated plate
        $stmt = $db_conn->prepare("
            INSERT INTO DonatedOrders (order_id, quantity_available) VALUES (?, ?)
        ");
        $stmt->bind_param("ii", $item["id"], $item["quantity"]);
        $stmt->execute();
    }

    $message = "Purchase successful!";
}

if (isset($_POST["cancel"])) {

    // Get all cart items
    $stmt = $db_conn->prepare("
        SELECT id, plate_id, quantity 
        FROM Orders 
        WHERE user_id = ? AND status='in_cart'
    ");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $cart = $stmt->get_result();

    while ($item = $cart->fetch_assoc()) {

        // Restore plate quantity
        $stmt2 = $db_conn->prepare("
            UPDATE Plates 
            SET quantity_available = quantity_available + ? 
            WHERE id = ?
        ");
        $stmt2->bind_param("ii", $item["quantity"], $item["plate_id"]);
        $stmt2->execute();

        // Mark order as cancelled
        $stmt3 = $db_conn->prepare("
            UPDATE Orders SET status='cancelled' 
            WHERE id = ?
        ");
        $stmt3->bind_param("i", $item["id"]);
        $stmt3->execute();
    }

    $message = "Checkout cancelled.";
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

    <a href="donor_homepage.php" class="btn btn-dark mb-4">Back</a>

    <?php if (!empty($message)) { ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php } ?>

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Reserved Plates in Your Cart</div>
        <div class="card-body">
            <?php
            $stmt = $db_conn->prepare("
                SELECT Orders.quantity, Orders.total_price, Plates.description
                FROM Orders
                JOIN Plates ON Orders.plate_id = Plates.id
                WHERE Orders.user_id=? AND Orders.status='in_cart'
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $plates = $stmt->get_result();
            $grand_total = 0;
            if ($plates->num_rows === 0) { 
                $actions_disabled = true; ?>
                <p>You do not have any plates.</p>
            <?php } else { ?>
                <table class="table table-bordered">
                    <tr>
                        <th>Plate</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                    <?php while ($p = $plates->fetch_assoc()) {
                        $grand_total += $p["total_price"]; ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p["description"]); ?></td>
                        <td><?php echo intval($p["quantity"]); ?></td>
                        <td>$<?php echo number_format($p["total_price"], 2); ?></td>
                    </tr>
            <?php }?>
                </table>
                <h4>Grand Total: $<?php echo number_format($grand_total, 2); ?></h4>
            <?php } ?>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Saved Credit Card</div>
        <div class="card-body">
            <?php
            $stmt = $db_conn->prepare("
                SELECT card_number, card_expiry
                FROM CreditCards
                WHERE user_id = ?
                LIMIT 1
            ");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $card = $stmt->get_result()->fetch_assoc();
            if (!$card) { ?>
                <p>You do not have a saved credit card on file.</p>
            <?php } else { ?>
                <p>You will use the following saved credit card on file to complete this purchase:</p>
                <p>Card ending in <strong><?php echo substr($card["card_number"], -4) ?></strong>
                with expiry <strong><?php echo $card["card_expiry"] ?></strong></p>
            <?php } ?>
        </div>
    </div>

    <form method="POST" class="d-flex gap-2">
        <button name="confirm" class="btn btn-success <?php if ($actions_disabled) echo "disabled" ?>">Confirm Purchase</button>
        <button name="cancel" class="btn btn-danger <?php if ($actions_disabled) echo "disabled" ?>">Cancel Purchase</button>
    </form>

</div>
</body>
</html>

<?php 
db_close();
?>
