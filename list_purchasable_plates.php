 <div class="card mb-4">
    <div class="card-header bg-primary text-white">Available Plates to Reserve</div>
    <div class="card-body">
        <table class="table table-bordered">
            <?php
            $plates = $db_conn->query("
                SELECT u.name, p.id, p.description, p.price, p.quantity_available
                FROM Plates p
                JOIN Users u ON p.owner_id = u.id
                WHERE quantity_available > 0
                AND NOW() BETWEEN available_from AND available_to
            ");
            if ($plates->num_rows === 0) { ?>
                <p>There are no plates that you can reserve at this time.</p>
            <?php } else { ?>
                <tr>
                    <th>Restaurant</th>
                    <th>Plate</th>
                    <th>Available Quantity</th>
                    <th>Price</th>
                    <th>Quantity to Reserve</th>
                </tr>
                <?php while ($p = $plates->fetch_assoc()) { ?>
                <tr>
                    <td><?php echo htmlspecialchars($p["name"]); ?></td>
                    <td><?php echo htmlspecialchars($p["description"]); ?></td>
                    <td><?php echo intval($p["quantity_available"]); ?></td>
                    <td>$<?php echo number_format($p["price"], 2); ?></td>
                    <td>
                        <form method='POST' class='d-flex gap-2'>
                            <input type='hidden' name='plate_id' value="<?php echo $p['id'] ?>">
                            <input class='form-control' type='number' name='quantity' min='1' max="<?php echo $p['quantity_available'] ?>" required>
                            <button class='btn btn-success'>Add</button>
                        </form>
                    </td>
                </tr>
            <?php } } ?>
        </table>
    </div>
</div>