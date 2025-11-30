<div class="card mb-4">
    <div class="card-header bg-secondary text-white">Your History</div>
    <div class="card-body">
        <table class="table table-bordered">
            <?php
            $history = $db_conn->query("
                SELECT u.name, p.description, o.quantity, o.total_price
                FROM Orders o
                JOIN Plates p ON p.id = o.plate_id
                JOIN Users u ON p.owner_id = u.id
                WHERE o.user_id = $user_id
                AND o.status = 'purchased'
            ");
            if ($history->num_rows === 0) { ?>
                <p>No history on record.</p>
            <?php } else { ?>
                <tr>
                    <th>Restaurant</th>
                    <th>Plate</th>
                    <th>Quantity</th>
                    <th>Total</th>
                </tr>
                <?php while ($h = $history->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($h["name"]); ?></td>
                    <td><?php echo htmlspecialchars($h["description"]); ?></td>
                    <td><?php echo intval($h["quantity"]); ?></td>
                    <td>$<?php echo number_format($h["total_price"], 2); ?></td>
                </tr>
            <?php } } ?>
        </table>
    </div>
</div>