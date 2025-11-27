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
$plateData = null;

db_open();
global $db_conn;

if (isset($_GET['id'])) {
    $plate_id = intval($_GET['id']);
    $owner_id = $_SESSION['user']['id'];
    
    $stmt = $db_conn->prepare("SELECT * FROM Plates WHERE id = ? AND owner_id = ?");
    $stmt->bind_param("ii", $plate_id, $owner_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $plateData = $result->fetch_assoc();
    $stmt->close();

    if (!$plateData) {
        header("Location: restaurant_homepage.php");
        exit();
    }
} else {
    header("Location: restaurant_homepage.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $quantity = intval($_POST['quantity']);
    $available_from = $_POST['available_from'];
    $available_to = $_POST['available_to'];

    if (empty($description) || empty($available_from) || empty($available_to)) {
        $message = "All fields are required.";
    } elseif ($price < 0 || $quantity < 0) {
        $message = "Price and Quantity cannot be negative.";
    } elseif (strtotime($available_to) <= strtotime($available_from)) {
        $message = "End time must be after start time.";
    } else {
        $sql = "UPDATE Plates SET description = ?, price = ?, quantity_available = ?, available_from = ?, available_to = ? WHERE id = ? AND owner_id = ?";
        
        $stmt = $db_conn->prepare($sql);
        $stmt->bind_param("sdissss", $description, $price, $quantity, $available_from, $available_to, $plate_id, $owner_id);

        if ($stmt->execute()) {
            $message = "Plate updated successfully!";
            
            $plateData['description'] = $description;
            $plateData['price'] = $price;
            $plateData['quantity_available'] = $quantity;
            $plateData['available_from'] = $available_from;
            $plateData['available_to'] = $available_to;
        } else {
            $message = "Error updating plate: " . $stmt->error;
        }
        $stmt->close();
    }
}
db_close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Plate</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <h1 class="mb-4">Edit Plate</h1>

            <?php if ($message): ?>
                <div class="alert alert-info mb-4">
                    <?= htmlspecialchars($message) ?> 
                    <a href="restaurant_homepage.php" class="alert-link">Return to Dashboard</a>
                </div>
            <?php endif; ?>

            <div class="card shadow-sm">
                <div class="card-body">
                    <form method="post">
                        <div class="mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" name="description" class="form-control" 
                                   value="<?php echo htmlspecialchars($plateData['description']); ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Price ($)</label>
                                <input type="number" step="0.01" name="price" class="form-control" 
                                       value="<?php echo htmlspecialchars($plateData['price']); ?>" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Quantity Available</label>
                                <input type="number" name="quantity" class="form-control" 
                                       value="<?php echo intval($plateData['quantity_available']); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">Available From</label>
                                <input type="datetime-local" name="available_from" class="form-control" 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($plateData['available_from'])); ?>" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Available To</label>
                                <input type="datetime-local" name="available_to" class="form-control" 
                                       value="<?php echo date('Y-m-d\TH:i', strtotime($plateData['available_to'])); ?>" required>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
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