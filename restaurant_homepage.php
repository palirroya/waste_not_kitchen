<?php
session_start();
require_once("auth.php");
require_once("database.php");

auth_init();

if (!$auth_is_logged_in || $_SESSION["user"]["role"] !== "restaurant") {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION["user"]["id"];

db_open();
global $db_conn;

// fetch plates owned by this restaurant
$query = "SELECT * FROM Plates WHERE owner_id = ? ORDER BY available_from DESC";
$stmt = $db_conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Restaurant Homepage</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>

<body class="bg-light">
    <div class="container py-4">

        <h1 class="mb-4">Welcome, <?= htmlspecialchars($_SESSION["user"]["name"]) ?></h1>

        <div class="mb-4">
            <a class="btn btn-dark" href="index.php">Home</a>
            <a class="btn btn-success" href="create_plate.php">+ Create New Plate</a>
            <a class="btn btn-danger" href="logout.php">Log out</a>
        </div>

        <div class="card mb-4">
            <div class="card-header bg-primary text-white">Your Menu Plates</div>
            <div class="card-body">
                <?php if ($result->num_rows === 0): ?>
                    <p>You haven't created any plates yet.</p>
                <?php else: ?>
                    <table class="table table-bordered table-striped">
                        <thead>
                            <tr>
                                <th>Description</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th>Available From</th>
                                <th>Available To</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['description']) ?></td>
                                    <td>$<?= number_format($row['price'], 2) ?></td>
                                    <td><?= intval($row['quantity_available']) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['available_from'])) ?></td>
                                    <td><?= date('M d, Y h:i A', strtotime($row['available_to'])) ?></td>
                                    <td>
                                        <a href="edit_plate.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm">Edit</a>

                                        <a href="delete_plate.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm"
                                            onclick="return confirm('Are you sure you want to delete this plate?');">
                                            Delete
                                        </a>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>

</html>

<?php
$stmt->close();
db_close();
?>