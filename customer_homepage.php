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
        <a class="btn btn-dark" href="customer_checkout.php">View my Cart</a>
        <a class="btn btn-danger" href="logout.php">Log out</a>
    </div>

    <?php if (!empty($message)): ?>
        <div class="alert alert-success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>     

    <div class="card mb-4">
        <div class="card-header bg-primary text-white">Available Plates to Reserve</div>
        <div class="card-body">
            <table class="table table-bordered">
                <?php
                $plates = $db_conn->query("
                    SELECT id, description, price, quantity_available
                    FROM Plates
                    WHERE quantity_available > 0
                    AND NOW() BETWEEN available_from AND available_to
                ");
                if ($plates->num_rows === 0) { ?>
                    <p>There are no plates that you can reserve at this time.</p>
                <?php } else { ?>
                    <tr>
                        <th>Plate</th>
                        <th>Available Quantity</th>
                        <th>Price</th>
                        <th>Quantity to Reserve</th>
                    </tr>
                    <?php while ($p = $plates->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($p["description"]); ?></td>
                        <td><?php echo intval($p["quantity_available"]); ?></td>
                        <td>$<?php echo number_format($p["price"], 2); ?></td>
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
                    SELECT orders.*, plates.description
                    FROM orders
                    JOIN plates ON plates.id = orders.plate_id
                    WHERE orders.user_id = $user_id
                    AND orders.status = 'purchased'
                ");
                if ($history->num_rows === 0) { ?>
                    <p>No history on record.</p>
                <?php } else { ?>
                    <tr>
                        <th>Plate</th>
                        <th>Quantity</th>
                        <th>Total</th>
                    </tr>
                    <?php while ($h = $history->fetch_assoc()) { ?>
                    <tr>
                        <td><?php echo htmlspecialchars($h["description"]); ?></td>
                        <td><?php echo intval($h["quantity"]); ?></td>
                        <td>$<?php echo number_format($h["total_price"], 2); ?></td>
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
