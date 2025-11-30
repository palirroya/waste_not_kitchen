<?php

$sql = null;

switch ($_SESSION["user"]["role"]) {
    case "customer":
    case "donor":
        $sql = "SELECT * FROM Orders WHERE user_id=" . $user_id . " AND status='in_cart'";
        break;
    case "in_need":
        $sql = "SELECT * FROM DonatedOrderClaims WHERE in_need_user_id=" . $user_id . " AND status='in_cart'";
        break;
}

echo mysqli_num_rows($db_conn->query($sql));

?>