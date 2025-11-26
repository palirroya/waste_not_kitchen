<?php
session_start();
require_once("auth.php");
auth_init();

if (!$auth_is_logged_in || $_SESSION["user"]["role"] !== "in_need") {
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION["user"]["id"];
require_once("database.php");
db_open();
$message = null;

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

        // Create claim
        $stmt = $db_conn->prepare("
            INSERT INTO DonatedOrderClaims (in_need_user_id, donated_order_id, quantity, status)
            VALUES (?, ?, ?, 'in_cart')
        ");
        $stmt->bind_param("iii", $user_id, $plate_id, $quantity);
        $stmt->execute();

        // Reduce plate quantity
        $stmt = $db_conn->prepare("
            UPDATE DonatedOrders
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
        <a class="btn btn-dark" href="in_need_checkout.php">View my Cart</a>
        <a class="btn btn-danger" href="logout.php">Log out</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>     

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Available Donated Plates to Reserve</div>
        <div class="card-body">
            <table class="table table-bordered">
                <?php
                $plates = $db_conn->query(" 
                    SELECT Plates.description, DonatedOrders.quantity_available, DonatedOrders.id
                    FROM Plates 
                    JOIN Orders ON Orders.plate_id = Plates.id 
                    JOIN DonatedOrders ON DonatedOrders.order_id = Orders.id 
                    WHERE DonatedOrders.quantity_available > 0;
                ");
                if ($plates->num_rows === 0) { ?>
                    <p>There are no donated plates that you can reserve at this time.</p>
                <?php } else { ?>
                    <tr>
                        <th>Plate</th>
                        <th>Available Quantity</th>
                        <th>Quantity to Reserve</th>
                    </tr>
                    <?php while ($p = $plates->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p["description"]); ?></td>
                        <td><?php echo intval($p["quantity_available"]); ?></td>
                        <td>
                            <form method='POST' class='d-flex gap-2'>
                                <input type='hidden' name='plate_id' value="<?php echo $p['id'] ?>">
                                <input class='form-control' type='number' name='quantity' min='1' max="<?php echo $p['quantity_available'] ?>" required>
                                <button class='btn btn-success'>Add</button>
                            </form>
                        </td>
                    </tr>
                <?php } } ?>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-secondary text-white">Your History</div>
        <div class="card-body">
            <table class="table table-bordered">
                <?php
                $history = $db_conn->query("
                    SELECT Plates.description, DonatedOrderClaims.quantity
                    FROM Plates
                    JOIN Orders ON Plates.id = Orders.plate_id
                    JOIN DonatedOrders ON DonatedOrders.order_id = Orders.id
                    JOIN DonatedOrderClaims ON DonatedOrderClaims.donated_order_id = DonatedOrders.id
                    WHERE in_need_user_id = $user_id
                    AND DonatedOrderClaims.status = 'claimed'
                ");
                if ($history->num_rows === 0) { ?>
                    <p>No history on record.</p>
                <?php } else { ?>
                    <tr>
                        <th>Plate</th>
                        <th>Quantity</th>
                    </tr>
                    <?php while ($h = $history->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($h["description"]); ?></td>
                        <td><?php echo intval($h["quantity"]); ?></td>
                    </tr>
                <?php } } ?>
            </table>
        </div>
    </div>

</div>
</body>
</html>

<?php 
db_close(); 
?>
