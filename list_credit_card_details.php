 <div class="card mb-4">
    <div class="card-header bg-secondary text-white">Saved Credit Card</div>
    <div class="card-body">
        <?php
        $stmt = $db_conn->prepare("
            SELECT card_number, card_expiry
            FROM CreditCards
            WHERE user_id = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $card = $stmt->get_result()->fetch_assoc();
        if (!$card) { ?>
            <p>You do not have a saved credit card on file.</p>
        <?php } else { ?>
            <p>You will use the following saved credit card on file to complete this purchase:</p>
            <p>Card ending in <strong><?php echo substr($card["card_number"], -4) ?></strong>
            with expiry <strong><?php echo $card["card_expiry"] ?></strong></p>
        <?php } ?>
    </div>
</div>