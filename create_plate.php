<?php
session_start();
require_once("auth.php");
require_once("database.php");

auth_init();

if (!$auth_is_logged_in || $_SESSION["user"]["role"] !== "restaurant") {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    // get plate info from user
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $available_from = $_POST['available_from'];
    $available_to = $_POST['available_to'];
    $owner_id = $_SESSION['user']['id'];

    if (empty($description) || empty($available_from) || empty($available_to)) {
        $error = "All fields are required.";
    } elseif ($price < 0 || $quantity < 0) {
        $error = "Price and Quantity cannot be negative.";
    } elseif (strtotime($available_to) <= strtotime($available_from)) {
        $error = "End time must be after start time.";
    } else {
        db_open();
        global $db_conn;

        $sql = "INSERT INTO Plates (owner_id, description, price, quantity_available, available_from, available_to) VALUES (?, ?, ?, ?, ?, ?)";

        $stmt = $db_conn->prepare($sql);
        $stmt->bind_param("isdiss", $owner_id, $description, $price, $quantity, $available_from, $available_to);

        if ($stmt->execute()) {
            $message = "Plate created successfully!";
        } else {
            $error = "Error creating plate: " . $stmt->error;
        }

        $stmt->close();
        db_close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Create New Plate</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <h1 class="mb-4">Create a New Plate</h1>
                <?php if ($message): ?>
                    <div class="alert alert-success"><?= htmlspecialchars($message) ?> <a
                            href="restaurant_homepage.php">Return to Menu</a></div>
                <?php endif; ?>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Description (e.g., "Pepperoni Pizza")</label>
                                <input type="text" name="description" class="form-control" required>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Price ($)</label>
                                    <input type="number" step="0.01" name="price" class="form-control" required>
                                </div>
                                <div class="col">
                                    <label class="form-label">Quantity Available</label>
                                    <input type="number" name="quantity" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <div class="col">
                                    <label class="form-label">Available From</label>
                                    <input type="datetime-local" name="available_from" class="form-control" required>
                                </div>
                                <div class="col">
                                    <label class="form-label">Available To</label>
                                    <input type="datetime-local" name="available_to" class="form-control" required>
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-success">Create Plate</button>
                                <a href="restaurant_homepage.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>