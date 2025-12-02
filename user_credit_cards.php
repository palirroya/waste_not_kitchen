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

db_open();
global $db_conn;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_card_id'])) {
    $delete_id = intval($_POST['delete_card_id']);
    
    $stmt_del = $db_conn->prepare("DELETE FROM CreditCards WHERE id = ? AND user_id = ?");
    $stmt_del->bind_param("ii", $delete_id, $user_id);
    
    if ($stmt_del->execute()) {
        $message = "Card removed successfully.";
        $toastClass = 'bg-success';
    } else {
        $message = "Error removing card.";
        $toastClass = 'bg-danger';
    }
    $stmt_del->close();
}

// fetch users cards
$cards = [];
$stmt_fetch = $db_conn->prepare("SELECT id, card_number, card_expiry FROM CreditCards WHERE user_id = ? ORDER BY id DESC");
$stmt_fetch->bind_param("i", $user_id);
$stmt_fetch->execute();
$result = $stmt_fetch->get_result();
while ($row = $result->fetch_assoc()) {
    $cards[] = $row;
}
$stmt_fetch->close();
db_close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Wallet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.css">
</head>
<body class="bg-light">
    <div class="container p-5 d-flex flex-column align-items-center">
        
        <?php if ($message): ?>
            <div class="toast align-items-center text-white <?php echo $toastClass; ?> border-0" 
                 role="alert" aria-live="assertive" aria-atomic="true">
                <div class="d-flex">
                    <div class="toast-body"><?php echo htmlspecialchars($message); ?></div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                </div>
            </div>
        <?php endif; ?>

        <div class="card mt-4 p-4 border-0" style="width: 100%; max-width: 600px; box-shadow: rgba(60, 64, 67, 0.3) 0px 1px 2px 0px, rgba(60, 64, 67, 0.15) 0px 2px 6px 2px;">
            
            <div class="text-center mb-4">
                <i class="fa fa-credit-card-alt fa-3x text-success mb-2"></i>
                <h4 class="fw-bold">My Wallet</h4>
                <p class="text-muted small">Manage your saved payment methods</p>
            </div>

            <div class="d-flex justify-content-end mb-3">
                <a href="edit_credit_card.php" class="btn btn-success btn-sm fw-bold">
                    <i class="fa fa-plus-circle"></i> Add New Card
                </a>
            </div>

            <?php if (empty($cards)): ?>
                <div class="alert alert-secondary text-center">
                    No cards saved yet.
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($cards as $card): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="fw-bold">**** <?php echo substr($card['card_number'], -4); ?></span>
                                <small class="text-muted ms-2">(Exp: <?php echo htmlspecialchars($card['card_expiry']); ?>)</small>
                            </div>
                            <div class="btn-group">
                                <a href="edit_credit_card.php?id=<?php echo $card['id']; ?>" class="btn btn-outline-secondary btn-sm" title="Edit">
                                    <i class="fa fa-pencil"></i>
                                </a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to remove this card?');" class="d-inline">
                                    <input type="hidden" name="delete_card_id" value="<?php echo $card['id']; ?>">
                                    <button type="submit" class="btn btn-outline-danger btn-sm" title="Delete" style="border-top-left-radius: 0; border-bottom-left-radius: 0;">
                                        <i class="fa fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <div class="mt-4 text-center">
                <a href="user_profile.php" class="text-decoration-none fw-bold" style="color: navy;">&larr; Back to Profile</a>
            </div>
        </div>
    </div>
    <script>
        var toastElList = [].slice.call(document.querySelectorAll('.toast'))
        var toastList = toastElList.map(function (toastEl) { return new bootstrap.Toast(toastEl, { delay: 3000 }); });
        toastList.forEach(toast => toast.show());
    </script>
</body>
</html>