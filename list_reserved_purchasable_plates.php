 <div class="card mb-4">
    <div class="card-header bg-primary text-white">Reserved Plates in Your Cart</div>
    <div class="card-body">
        <?php
        $stmt = $db_conn->prepare("
            SELECT u.name, o.quantity, o.total_price, p.description
            FROM Orders o
            JOIN Plates p ON o.plate_id = p.id
            JOIN Users u ON p.owner_id = u.id
            WHERE o.user_id=? AND o.status='in_cart'
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $plates = $stmt->get_result();
        $grand_total = 0;
        if ($plates->num_rows === 0) {
            $actions_disabled = true; ?>
            <p>You do not have any plates.</p>
        <?php } else { ?>
            <table class="table table-bordered">
                <tr>
                    <th>Restaurant</th>
                    <th>Plate</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
                <?php while ($p = $plates->fetch_assoc()) {
                    $grand_total += $p["total_price"]; ?>
                <tr>
                    <td><?php echo htmlspecialchars($p["name"]); ?></td>
                    <td><?php echo htmlspecialchars($p["description"]); ?></td>
                    <td><?php echo intval($p["quantity"]); ?></td>
                    <td>$<?php echo number_format($p["total_price"], 2); ?></td>
                </tr>
        <?php }?>
            </table>
            <h4>Grand Total: $<?php echo number_format($grand_total, 2); ?></h4>
        <?php } ?>
    </div>
</div>