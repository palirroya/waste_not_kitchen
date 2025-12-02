<?php
session_start();
require 'database.php';
require 'auth.php';

auth_init();
if (!$auth_is_logged_in) {
    header("Location: login.php");
    exit();
}

$message = '';
$toastClass = '';
$user_id = $_SESSION["user"]["id"];

$card_id = null;
$card_number = '';
$card_expiry = '';
$card_cvv = '';
$is_edit_mode = false;

db_open();
global $db_conn;

if (isset($_GET['id'])) {
    $card_id = intval($_GET['id']);
    
    // etch card details
    $stmt = $db_conn->prepare("SELECT card_number, card_expiry, card_cvv FROM CreditCards WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ii", $card_id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $card_number = $row['card_number'];
        $card_expiry = $row['card_expiry'];
        $card_cvv = $row['card_cvv'];
        $is_edit_mode = true;
    } else {
        header("Location: user_credit_cards.php");
        exit();
    }
    $stmt->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_number = trim($_POST['card_number'] ?? '');
    $new_expiry = trim($_POST['card_expiry'] ?? '');
    $new_cvv = trim($_POST['card_cvv'] ?? '');
    
    if (empty($new_number) || empty($new_expiry) || empty($new_cvv)) {
        $message = "All fields are required.";
        $toastClass = 'bg-danger';
    } 
    else {
        if ($is_edit_mode) {
            $stmt = $db_conn->prepare("UPDATE CreditCards SET card_number = ?, card_expiry = ?, card_cvv = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param("sssii", $new_number, $new_expiry, $new_cvv, $card_id, $user_id);
        } else {
            $stmt = $db_conn->prepare("INSERT INTO CreditCards (user_id, card_number, card_expiry, card_cvv) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("isss", $user_id, $new_number, $new_expiry, $new_cvv);
        }

        if ($stmt->execute()) {
            header("Location: user_credit_cards.php");
            exit();
        } else {
            $message = "Database error: " . $stmt->error;
            $toastClass = 'bg-danger';
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
    <title><?php echo $is_edit_mode ? 'Edit Card' : 'Add Card'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        
        <?php if ($message): ?>
            <div class="toast align-items-center text-white <?php echo $toastClass; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mt-4 p-4 border-0" style="width: 100%; max-width: 500px; box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            <div class="text-center mb-4">
                <i class="fa fa-credit-card fa-3x text-primary mb-2"></i>
                <h4 class="fw-bold"><?php echo $is_edit_mode ? 'Edit Card' : 'Add New Card'; ?></h4>
            </div>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label small">Card Number</label>
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fa fa-hashtag"></i></span>
                        <input type="text" name="card_number" class="form-control" 
                               value="<?php echo htmlspecialchars($card_number); ?>"
                               placeholder="Any number for testing" required>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-6">
                        <label class="form-label small">Expiry (MM/YY)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa fa-calendar"></i></span>
                            <input type="text" name="card_expiry" class="form-control" 
                                   value="<?php echo htmlspecialchars($card_expiry); ?>"
                                   placeholder="MM/YY" required>
                        </div>
                    </div>
                    <div class="col-6">
                        <label class="form-label small">CVV</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white"><i class="fa fa-lock"></i></span>
                            <input type="text" name="card_cvv" class="form-control" 
                                   value="<?php echo htmlspecialchars($card_cvv); ?>"
                                   placeholder="123" required>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary fw-bold">
                        <?php echo $is_edit_mode ? 'Save Changes' : 'Add Card'; ?>
                    </button>
                    <a href="user_credit_cards.php" class="btn btn-outline-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
    <script>
        var toastElList = [].slice.call(document.querySelectorAll('.toast'))
        var toastList = toastElList.map(function (toastEl) { return new bootstrap.Toast(toastEl, { delay: 3000 }); });
        toastList.forEach(toast => toast.show());
    </script>
</body>
</html>