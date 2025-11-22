<?php
require 'database.php';
require 'auth.php';

auth_init();

$message     = '';
$toastClass  = 'bg-danger';
$redirectUrl = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    db_open();
    global $db_conn;

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username === '' || $password === '') {
        $message = "Username and password are required.";
    } else {
        $password_hashed = hash('sha256', $password);

        $sql = "SELECT id, role, username, password_hash FROM users WHERE username = ?";
        $stmt = mysqli_prepare($db_conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            if (hash_equals($row['password_hash'], $password_hashed)) {
                $userData = [
                    "id"       => $row["id"],
                    "role"     => $row["role"],
                    "username" => $row["username"]
                ];

                // set $auth_is_logged_in = true
                auth_log_in($userData);

                // redirect based on role
                switch ($row['role']) {
                    case 'admin':
                        $redirect = 'admin_homepage.php';
                        break;
                    case 'restaurant':
                        $redirect = 'restaurant_homepage.php';
                        break;
                    case 'customer':
                        $redirect = 'customer_homepage.php';
                        break;
                    case 'donor':
                        $redirect = 'donor_homepage.php';
                        break;
                    case 'in_need':
                        $redirect = 'in_need_homepage.php';
                        break;
                }

                $message     = "Login successful! Redirecting...";
                $toastClass  = 'bg-success';
                $redirectUrl = $redirect;   // used below in meta refresh
            } else {
                $message = "Invalid username or password.";
            }
        } else {
            $message = "Invalid username or password.";
        }

        mysqli_stmt_close($stmt);
    }

    db_close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" 
          content="width=device-width, initial-scale=1.0">

    <?php if (!empty($redirectUrl)): ?>
        <!-- 1.5s delay before redirecting to the role-specific homepage -->
        <meta http-equiv="refresh" content="1.5;url=<?php echo htmlspecialchars($redirectUrl); ?>">
    <?php endif; ?>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" 
          rel="stylesheet">
    <link rel="stylesheet" 
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
    <link rel="shortcut icon" 
          href="https://cdn-icons-png.flaticon.com/512/295/295128.png">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/js/bootstrap.bundle.min.js">
    </script>
    <title>Login Page</title>
</head>

<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        <?php if ($message): ?>
            <div class="toast align-items-center text-white 
            <?php echo $toastClass; ?> border-0" role="alert"
                aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                    <button type="button" class="btn-close
                    btn-close-white me-2 m-auto" data-bs-dismiss="toast"
                        aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <form action="" method="post" class="form-control mt-5 p-4"
            style="height:auto; width:380px; box-shadow: rgba(60, 64, 67, 0.3) 
            0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            <div class="row">
                <i class="fa fa-user-circle-o fa-3x mt-1 mb-2"
                   style="text-align: center; color: green;"></i>
                <h5 class="text-center p-4" 
                    style="font-weight: 700;">Login Into Your Account</h5>
            </div>
            <div class="col-mb-3">
                <label for="username"><i 
                  class="fa fa-user"></i> Username</label>
                <input type="text" name="username" id="username"
                       class="form-control" required>
            </div>
            <div class="col mb-3 mt-3">
                <label for="password"><i
                  class="fa fa-lock"></i> Password</label>
                <input type="password" name="password" id="password" 
                       class="form-control" required>
            </div>
            <div class="col mb-3 mt-3">
                <button type="submit" 
                        class="btn btn-success bg-success" style="font-weight: 600;">
                    Login
                </button>
            </div>
            <div class="col mb-2 mt-4">
                <p class="text-center" 
                   style="font-weight: 600; color: navy;">
                   <a href="./register.php" style="text-decoration: none;">Create Account</a>
                   OR
                   <a href="./reset_password.php" style="text-decoration: none;">Forgot Password</a>
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
