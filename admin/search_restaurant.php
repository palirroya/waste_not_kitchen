<?php
// Debug output
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

//require_once "auth.php";
require_once "../database.php";
db_open();

$search = "";
$restaurants = [];

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);

    $stmt = $db_conn->prepare("SELECT id, name, username, address, phone
        FROM Users
        WHERE role = 'restaurant'
        AND name LIKE ?
        ");


    $like = "%" . $search . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();

    // Instead of get_result(), we bind manually.
    $stmt->bind_result($id, $name, $username, $address, $phone);

    while ($stmt->fetch()) {
        $restaurants[] = [
            "id" => $id,
            "name" => $name,
            "username" => $username,
            "address" => $address,
            "phone" => $phone
        ];
    }

    $stmt->close();
}
db_close();
?>
<!DOCTYPE html>
<html>
<head>
    <title>Search Restaurants</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        input[type=text] { width: 300px; padding: 8px; font-size: 16px; }
        button { padding: 8px 14px; font-size: 16px; cursor: pointer; }
        .restaurant { padding: 10px; border: 1px solid #ddd; margin-top: 8px; border-radius: 5px; }
        .restaurant h3 { margin: 0; }
    </style>
</head>
<body>

<h2>Search Restaurants</h2>

<form method="GET" action="">
    <input 
        type="text" 
        name="search" 
        placeholder="Enter restaurant name..." 
        value="<?php echo htmlspecialchars($search); ?>"
        required
    >
    <button type="submit">Search</button>
</form>

<hr><br>

<?php if (!empty($search)): ?>

    <?php if (count($restaurants) > 0): ?>

        <?php foreach ($restaurants as $r): ?>
            <a href="restaurant_report.php?id=<?php echo $r['id']; ?>" style="text-decoration:none; color:inherit;">
                <div class="restaurant">
                    <h3><?php echo htmlspecialchars($r["name"]); ?></h3>
                    <p>
                        <strong>Address:</strong> <?php echo htmlspecialchars($r["address"]); ?><br>
                        <strong>Restaurant ID:</strong> <?php echo htmlspecialchars($r["id"]); ?>
                    </p>
                </div>
            </a>
        <?php endforeach; ?>

    <?php else: ?>
        <p>No restaurants found matching "<?php echo htmlspecialchars($search); ?>".</p>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>