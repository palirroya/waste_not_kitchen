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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
</head>

<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">

        <div class="card p-4 border-0"
            style="width: 100%; max-width: 1000px; box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">

            <div class="text-center mb-4">
                <i class="fa fa-cutlery fa-4x mb-3" style="color: green;"></i>
                <h2 style="font-weight: 700;">Restaurant Dashboard</h2>
                <p class="text-muted">Welcome back, <?= htmlspecialchars($_SESSION["user"]["name"]) ?></p>
            </div>

            <div class="row mb-4 justify-content-center">
                <div class="col-auto">
                    <a class="btn btn-outline-dark me-2" href="index.php"><i class="fa fa-home"></i> Home</a>
                    <a class="btn btn-success me-2" href="create_plate.php" style="font-weight: 600;"><i
                            class="fa fa-plus-circle"></i> Create Plate</a>
                    <a class="btn btn-warning me-2" href="user_profile.php" style="font-weight: 600;"><i
                            class="fa fa-user"></i> Profile</a>
                    <a class="btn btn-danger" href="logout.php"><i class="fa fa-sign-out"></i> Log out</a>
                </div>
            </div>

            <div class="mt-2">
                <h5 class="mb-3"
                    style="color: navy; font-weight: 700; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                    <i class="fa fa-list-alt"></i> Your Menu Items
                </h5>

                <?php if ($result->num_rows === 0): ?>
                    <div class="alert alert-secondary text-center">
                        <i class="fa fa-info-circle"></i> You haven't created any plates yet.
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Qty</th>
                                    <th>Available From</th>
                                    <th>Available To</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td class="fw-bold"><?= htmlspecialchars($row['description']) ?></td>
                                        <td class="text-success">$<?= number_format($row['price'], 2) ?></td>
                                        <td>
                                            <span class="badge bg-secondary rounded-pill">
                                                <?= intval($row['quantity_available']) ?>
                                            </span>
                                        </td>
                                        <td class="small text-muted">
                                            <?= date('M d, h:i A', strtotime($row['available_from'])) ?></td>
                                        <td class="small text-muted"><?= date('M d, h:i A', strtotime($row['available_to'])) ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="edit_plate.php?id=<?= $row['id'] ?>"
                                                    class="btn btn-outline-warning btn-sm" title="Edit">
                                                    <i class="fa fa-pencil"></i>
                                                </a>
                                                <a href="delete_plate.php?id=<?= $row['id'] ?>"
                                                    class="btn btn-outline-danger btn-sm"
                                                    onclick="return confirm('Are you sure you want to delete this plate?');"
                                                    title="Delete">
                                                    <i class="fa fa-trash"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
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