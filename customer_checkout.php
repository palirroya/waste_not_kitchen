<?php

require_once("auth.php");
auth_init();
if (!$auth_is_logged_in && $_SESSION["user"]["role"] !== "customer") {
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

    <a href="customer_homepage.php" class="btn btn-dark mb-4">Back</a>

    <?php if (!empty($message)) { ?>
        <div class="alert alert-info"><?= htmlspecialchars($message) ?></div>
    <?php } ?>

   <?php require_once("list_reserved_purchasable_plates.php"); ?>

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
