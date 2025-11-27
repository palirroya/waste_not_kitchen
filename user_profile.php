<?php
session_start();
require 'database.php';
require 'auth.php';

// authenticate user
auth_init();
if (!$auth_is_logged_in) {
    header("Location: login.php");
    exit();
}

$message = '';
$toastClass = '';

$user_id = $_SESSION["user"]["id"];

db_open();
global $db_conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if ($name === '') {
        $message = "Name is required.";
        $toastClass = 'bg-danger';
    } else {
        $sql = "UPDATE users SET name = ?, address = ?, phone = ? WHERE id = ?";
        $stmt = mysqli_prepare($db_conn, $sql);
        mysqli_stmt_bind_param($stmt, "sssi", $name, $address, $phone, $user_id);

        if (mysqli_stmt_execute($stmt)) {
            $message = "Profile updated successfully!";
            $toastClass = 'bg-success';

            // update session data immediately
            $_SESSION["user"]["name"] = $name;
        } else {
            $message = "Error updating profile: " . mysqli_stmt_error($stmt);
            $toastClass = 'bg-danger';
        }
        mysqli_stmt_close($stmt);
    }
}

// fetch current user data to pre-fill the form
$sql_fetch = "SELECT username, name, address, phone FROM users WHERE id = ?";
$stmt_fetch = mysqli_prepare($db_conn, $sql_fetch);
mysqli_stmt_bind_param($stmt_fetch, "i", $user_id);
mysqli_stmt_execute($stmt_fetch);
$result = mysqli_stmt_get_result($stmt_fetch);
$userData = mysqli_fetch_assoc($result);
mysqli_stmt_close($stmt_fetch);

db_close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js"></script>
    <title>Edit Profile</title>
</head>

<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">

        <?php if ($message): ?>
            <div class="toast align-items-center text-white <?php echo $toastClass; ?> border-0" role="alert"
                aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <form method="post" class="form-control mt-5 p-4"
            style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">

            <div class="row text-center mb-3">
                <div class="col-12">
                    <i class="fa fa-id-card-o fa-3x mt-1 mb-2" style="color: green;"></i>
                    <h5 class="p-2" style="font-weight: 700;">Edit Profile</h5>
                </div>
            </div>
            <hr class="my-4">
            <div class="mb-3">
                <label for="name" class="form-label"><i class="fa fa-address-card"></i> Name <span
                        class="text-muted small">(Required)</span></label>
                <input type="text" name="name" id="name" class="form-control"
                    value="<?php echo htmlspecialchars($userData['name']); ?>" required>
            </div>
            <div class="mb-3">
                <label for="address" class="form-label"><i class="fa fa-map-marker"></i> Address</label>
                <input type="text" name="address" id="address" class="form-control"
                    value="<?php echo htmlspecialchars($userData['address']); ?>">
            </div>
            <div class="mb-3">
                <label for="phone" class="form-label"><i class="fa fa-phone"></i> Phone</label>
                <input type="tel" name="phone" id="phone" class="form-control"
                    value="<?php echo htmlspecialchars($userData['phone']); ?>">
            </div>
            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-success" style="font-weight: 600;">Update Profile</button>
            </div>
            <div class="mt-3 text-center">
                <p style="font-weight: 600; color: navy;">
                    Back to <a href="user_homepage.php" style="text-decoration: none;">Homepage</a>
                </p>
            </div>
        </form>
    </div>

    <script>
        var toastElList = [].slice.call(document.querySelectorAll('.toast'))
        var toastList = toastElList.map(function (toastEl) {
            return new bootstrap.Toast(toastEl, { delay: 3000 });
        });
        toastList.forEach(toast => toast.show());
    </script>
</body>

</html>