<?php
require "db.php";
session_start();

// Logged in
if (!isset($_SESSION['customer_id'])) {
    header("Location: login.php");
    exit();
}

$message = "";
$order_id = null;
$error_product_id = null;

try {
    $dbh = connectDB();
    
    // Call the procedure to 
    $stmt = $dbh->prepare("CALL checkout(:customer_id, @p_order_id, @p_out_of_stock_product)");
    $stmt->bindParam(':customer_id', $_SESSION['customer_id'], PDO::PARAM_INT);
    $stmt->execute();
    $stmt->closeCursor();

    // Retrieve the output parameters
    $result = $dbh->query("SELECT @p_order_id AS order_id, @p_out_of_stock_product AS error_id")->fetch(PDO::FETCH_ASSOC);

    if ($result['order_id']) { // Succeded
        $order_id = $result['order_id'];
        $message = "Order placed successfully! Your Order ID is: #$order_id";
    } elseif ($result['error_id']) { // Failed
        $error_product_id = $result['error_id'];
        
        // Blame the error on a product
        $prodStmt = $dbh->prepare("SELECT name, stock FROM Product WHERE product_id = :id");
        $prodStmt->execute([':id' => $error_product_id]);
        $prodData = $prodStmt->fetch(PDO::FETCH_ASSOC);
        
        $message = "Checkout failed. We do not have enough stock for: " . htmlspecialchars($prodData['name']) . 
                   " (Remaining: " . $prodData['stock'] . "). Please update your cart.";
    } else {         // Failure due to no data
        $message = "Checkout failed. Your cart might be empty.";
    }

    $dbh = null;
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Checkout Status</title>
</head>
<body>
    <h1>Checkout Status</h1>
    
    <?php if ($order_id): ?>
        <h2 style="color: green;"><?= htmlspecialchars($message) ?></h2>
        <p>Thank you for your purchase.</p>
        <a href="store.php?view=orders">View Your Orders</a>
        
    <?php else: ?>
        <h2 style="color: red;"><?= htmlspecialchars($message) ?></h2>
        <a href="store.php?view=cart">Return to Cart</a>
    <?php endif; ?>

    <br><br>
    <a href="store.php">Back to Store</a>
</body>
</html>