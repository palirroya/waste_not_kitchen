<?php

require_once("auth.php");
auth_init();
if (!$auth_is_logged_in || $_SESSION["user"]["role"] !== "customer") {
    header("Location: login.php");
    exit();
}

require_once("database.php");
db_open();
$message = null;
$user_id = $_SESSION["user"]["id"];

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["plate_id"], $_POST["quantity"])) {
    $plate_id = intval($_POST["plate_id"]);
    $quantity = intval($_POST["quantity"]);

    $stmt = $db_conn->prepare("SELECT price FROM Plates WHERE id=? LIMIT 1");
    $stmt->bind_param("i", $plate_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plate = $result->fetch_assoc();

    if ($plate) {
        $total = $plate["price"] * $quantity;

        // Create order
        $stmt = $db_conn->prepare("
            INSERT INTO Orders (user_id, plate_id, quantity, total_price, status)
            VALUES (?, ?, ?, ?, 'in_cart')
        ");
        $stmt->bind_param("iiid", $user_id, $plate_id, $quantity, $total);
        $stmt->execute();

        // Reduce plate quantity
        $stmt = $db_conn->prepare("
            UPDATE Plates
            SET quantity_available = quantity_available - ?
            WHERE id = ?
        ");
        $stmt->bind_param("ii", $quantity, $plate_id);
        $stmt->execute();

        $message = "Added to cart!";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Homepage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<div class="container py-4">

    <h1 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION["user"]["name"]) ?></h1>

    <div class="mb-4">
        <a class="btn btn-dark" href="index.php">Home</a>
        <a class="btn btn-dark" href="user_profile.php">My Profile</a>
        <a class="btn btn-dark" href="customer_checkout.php">View my Cart (<?php require_once("cart_size.php"); ?>)</a>
        <a class="btn btn-danger float-end" href="logout.php">Log out</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>     

    <?php require_once("list_purchasable_plates.php"); ?>

    <?php require_once("list_purchased_plates.php"); ?>

</div>
</body>
</html>

<?php 
db_close(); 
?>
