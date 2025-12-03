<?php



require_once "../database.php";
db_open();

$search = "";
$consumers = [];

if (isset($_GET["search"])) {
    $search = trim($_GET["search"]);

    $stmt = $db_conn->prepare("SELECT id, name, username, address, phone
        FROM Users
        WHERE role = 'in_need'
        AND name LIKE ?
        ");


    $like = "%" . $search . "%";
    $stmt->bind_param("s", $like);
    $stmt->execute();
    $stmt->bind_result($id, $name, $username, $address, $phone);

    while ($stmt->fetch()) {
        $consumers[] = [
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
    <title>Search Registered In Needs</title>
    <style>
        body { font-family: Arial; margin: 30px; }
        input[type=text] { width: 300px; padding: 8px; font-size: 16px; }
        button { padding: 8px 14px; font-size: 16px; cursor: pointer; }
        .consumer { padding: 10px; border: 1px solid #ddd; margin-top: 8px; border-radius: 5px; }
        .consumer h3 { margin: 0; }
    </style>
</head>
<body>

<h2>Search Registered In Needs</h2>

<form method="GET" action="">
    <input 
        type="text" 
        name="search" 
        placeholder="Enter a name..." 
        value="<?php echo htmlspecialchars($search); ?>"
        required
    >
    <button type="submit">Search</button>
</form>

<hr><br>

<?php if (!empty($search)): ?>

    <?php if (count($consumers) > 0): ?>

        <?php foreach ($consumers as $r): ?>
            <a href="needy_report.php?id=<?php echo $r['id']; ?>" style="text-decoration:none; color:inherit;">
                <div class="consumer">
                    <h3><?php echo htmlspecialchars($r["name"]); ?></h3>
                    <p>
                        <strong>Address:</strong> <?php echo htmlspecialchars($r["address"]); ?><br>
                        <strong>ID:</strong> <?php echo htmlspecialchars($r["id"]); ?>
                    </p>
                </div>
            </a>
        <?php endforeach; ?>

    <?php else: ?>
        <p>No registered in needs found matching "<?php echo htmlspecialchars($search); ?>".</p>
    <?php endif; ?>

<?php endif; ?>

</body>
</html>