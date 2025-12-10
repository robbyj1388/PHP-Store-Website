<?php
require "db.php";
session_start();

// ---------------------
// LOGOUT HANDLING
// ---------------------
$showLogoutConfirm = false;
if (isset($_POST['logout_confirm'])) {
    $showLogoutConfirm = true;
}

if (isset($_POST['confirm_logout'])) {
    session_unset();
    session_destroy();
    header("Location: store.php");
    exit();
}

if (isset($_POST['cancel_logout'])) {
    header("Location: store.php");
    exit();
}

// ADD TO CART HANDLING
$cartMessage = "";
if (isset($_POST['add_to_cart']) && isset($_SESSION['customer_id'])) {
    $product_id = intval($_POST['product_id']);
    $quantity = intval($_POST['quantity']);
    $customer_id = $_SESSION['customer_id'];

    try {
        $dbh = connectDB();

        // Get product name
        $stmt = $dbh->prepare("SELECT name FROM Product WHERE product_id = :product_id");
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        // Check if product already in cart
        $stmt = $dbh->prepare("SELECT quantity FROM ShoppingCart WHERE customer_id = :customer_id AND product_id = :product_id");
        $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->execute();
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $newQuantity = $existing['quantity'] + $quantity;
            $stmt = $dbh->prepare("UPDATE ShoppingCart SET quantity = :quantity WHERE customer_id = :customer_id AND product_id = :product_id");
            $stmt->bindParam(":quantity", $newQuantity, PDO::PARAM_INT);
        } else {
            $stmt = $dbh->prepare("INSERT INTO ShoppingCart (customer_id, product_id, quantity) VALUES (:customer_id, :product_id, :quantity)");
            $stmt->bindParam(":quantity", $quantity, PDO::PARAM_INT);
        }

        $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id, PDO::PARAM_INT);
        $stmt->execute();

        $cartMessage = "Added {$quantity} x '{$product['name']}' to your cart!";
        $dbh = null;
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}

// ---------------------
// CATEGORY SELECTION
// ---------------------
try {
    $dbh = connectDB();
    $stmt = $dbh->prepare("SELECT category_id, name FROM Category ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dbh = null;
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// ---------------------
// PRODUCT SEARCH
// ---------------------
$searchResults = [];
if (isset($_GET['search_category'])) {
    $category_id = intval($_GET['search_category']);
    try {
        $dbh = connectDB();
        $stmt = $dbh->prepare("SELECT * FROM Product WHERE category_id = :category_id AND discontinued = FALSE");
        $stmt->bindParam(":category_id", $category_id, PDO::PARAM_INT);
        $stmt->execute();
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}

// ---------------------
// SHOW SHOPPING CART
// ---------------------
$showCart = false;
$cartItems = [];
if (isset($_GET['view']) && $_GET['view'] === 'cart' && isset($_SESSION['customer_id'])) {
    $showCart = true;
    try {
        $dbh = connectDB();
        // Added product id to refer to when removing an item from the cart
        $stmt = $dbh->prepare("
            SELECT p.product_id, p.name, p.price, s.quantity
            FROM ShoppingCart s
            JOIN Product p ON s.product_id = p.product_id
            WHERE s.customer_id = :customer_id
        ");
        $stmt->bindParam(":customer_id", $_SESSION['customer_id'], PDO::PARAM_INT);
        $stmt->execute();
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}

// ---------------------
// VIEW ORDERS HANDLING
// ---------------------
$orders = [];
$showOrders = false;
if (isset($_GET['view']) && $_GET['view'] === 'orders' && isset($_SESSION['customer_id'])) {
    $showOrders = true;
    try {
        $dbh = connectDB();
        
        // Get all orders for this customer
        $stmt = $dbh->prepare("SELECT order_id, date, cost, status FROM `Order` WHERE customer_id = :cid ORDER BY date DESC");
        $stmt->execute([':cid' => $_SESSION['customer_id']]);
        $ordersRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get all the items for each order
        foreach ($ordersRaw as $ord) {
            $o_id = $ord['order_id'];
            
            // Get the name of the order
            $itemStmt = $dbh->prepare("
                SELECT oi.product_id, p.name, oi.price, oi.quantity 
                FROM OrderItem oi
                JOIN Product p ON oi.product_id = p.product_id
                WHERE oi.order_id = :oid
            ");
            $itemStmt->execute([':oid' => $o_id]);
            $items = $itemStmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Add items to the order array
            $ord['items'] = $items;
            $orders[] = $ord;
        }
        $dbh = null;
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}

// ---------------------
// REMOVE FROM CART
// ---------------------
if (isset($_POST['remove_item']) && isset($_SESSION['customer_id'])) {
    // Get the ID of the product to remove
    $product_id_to_remove = intval($_POST['product_id']);
    $customer_id = $_SESSION['customer_id'];

    try {
        $dbh = connectDB();
        // Delete the selected row
        $stmt = $dbh->prepare("DELETE FROM ShoppingCart WHERE customer_id = :customer_id AND product_id = :product_id");
        $stmt->bindParam(":customer_id", $customer_id, PDO::PARAM_INT);
        $stmt->bindParam(":product_id", $product_id_to_remove, PDO::PARAM_INT);
        $stmt->execute();
        
        $cartMessage = "Item removed from cart.";
        $dbh = null;
        
        // Refresh the page to show the updated cart
        header("Location: " . $_SERVER['PHP_SELF'] . "?view=cart");
        exit();
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Customer Main Page</title>
</head>
<body>

<?php if ($showLogoutConfirm): ?>
    <h3>Are you sure you want to log out?</h3>
    <form method="POST">
        <input type="submit" name="confirm_logout" value="Yes, Log me out">
        <input type="submit" name="cancel_logout" value="Cancel">
    </form>

<?php elseif (isset($_SESSION["customer_id"])): ?>
    <h1>Welcome back!</h1>

    <!-- Customer buttons -->
    <form method="GET" action="store.php" style="display:inline;">
        <input type="hidden" name="view" value="orders">
        <input type="submit" value="View Orders">
    </form>
    <form method="GET" action="store.php" style="display:inline;">
        <input type="hidden" name="view" value="cart">
        <input type="submit" value="Shopping Cart">
    </form>
    <form method="GET" action="reset_password.php" style="display:inline;">
        <input type="submit" value="Change Password">
    </form>
    <form method="POST" style="display:inline;">
        <input type="submit" name="logout_confirm" value="Logout">
    </form>

<?php else: ?>
    <h1>Welcome to Our Store</h1>
    <p><a href="login.php">Login</a></p>
<?php endif; ?>

<!-- Category selection -->
<form method="GET">
    <label for="category">Select a category:</label>
    <select name="search_category" id="category">
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="Search">
</form>

<!-- Cart message -->
<?php if (!empty($cartMessage)): ?>
    <p style="color:green"><?= htmlspecialchars($cartMessage) ?></p>
<?php endif; ?>

<!-- Display shopping cart -->
<?php if ($showCart): ?>
    
    <!-- Message for when the cart is empty -->
    <?php if (empty($cartItems)): ?>
        <h2>Your Shopping Cart is Empty</h2>
        <p>Please add some products to your cart.</p>
    <?php else: ?>
        <h2>Your Shopping Cart</h2>
        <table border="1" cellpadding="5">
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
                <th>Action</th> </tr>
            <?php
            $total = 0;
            foreach ($cartItems as $item):
                $subtotal = $item['price'] * $item['quantity'];
                $total += $subtotal;
            ?>
                <tr>
                    <td><?= htmlspecialchars($item['name']) ?></td>
                    <td>$<?= number_format($item['price'], 2) ?></td>
                    <td><?= $item['quantity'] ?></td>
                    <td>$<?= number_format($subtotal, 2) ?></td>
                    
                    <!-- This button will remove the current item from the shopping cart using the product id added earlier -->
                    <td>
                        <form method="POST">
                            <input type="hidden" name="product_id" value="<?= $item['product_id'] ?>">
                            <input type="submit" name="remove_item" value="Remove" style="color:red; cursor:pointer;">
                        </form>
                    </td>
                    
                </tr>
            <?php endforeach; ?>
            <tr>
                <td colspan="3"><strong>Total</strong></td>
                <td colspan="2"><strong>$<?= number_format($total, 2) ?></strong></td>
            </tr>
        </table>
        <form method="GET" action="checkout.php" style="margin-top:10px;">
            <input type="submit" value="Checkout">
        </form>
    <?php endif; ?>

<!-- Show order history here -->
<?php elseif ($showOrders): ?>
    <h2>Your Order History</h2>
    <?php if (empty($orders)): ?>
        <p>You have not placed any orders yet.</p>
    <?php else: ?>
        <?php foreach ($orders as $order): ?>
            <div style="border: 1px solid #ccc; padding: 10px; margin-bottom: 20px;">
                <h3>Order ID: <?= $order['order_id'] ?></h3>
                <p><strong>Date:</strong> <?= $order['date'] ?> | <strong>Total:</strong> $<?= number_format($order['cost'], 2) ?> | <strong>Status:</strong> <?= $order['status'] ?></p>
                
                <table border="1" cellpadding="5" style="width: 100%;">
                    <tr style="background-color: #f2f2f2;">
                        <th>Product ID</th>
                        <th>Product Name</th>
                        <th>Price Sold</th>
                        <th>Quantity</th>
                    </tr>
                    <?php foreach ($order['items'] as $item): ?>
                        <tr>
                            <td><?= $item['product_id'] ?></td>
                            <td><?= htmlspecialchars($item['name']) ?></td>
                            <td>$<?= number_format($item['price'], 2) ?></td>
                            <td><?= $item['quantity'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>

<?php elseif (!empty($searchResults)): ?>
    <h2>Products in selected category</h2>
    <ul>
        <?php foreach ($searchResults as $product): ?>
            <li>
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['description']) ?></p>
                <p>Price: $<?= number_format($product['price'], 2) ?></p>
                <p>Stock: <?= $product['stock'] ?></p>
                <?php if ($product['image']): ?>
                    <img src="<?= $product['image'] ?>" alt="<?= htmlspecialchars($product['name']) ?>" width="150">
                <?php endif; ?>

                <?php if (isset($_SESSION['customer_id'])): ?>
                    <form method="POST" style="margin-top:5px;">
                        <input type="hidden" name="product_id" value="<?= $product['product_id'] ?>">
                        <input type="number" name="quantity" value="1" min="1" max="<?= $product['stock'] ?>" required>
                        <input type="submit" name="add_to_cart" value="Add to Cart">
                    </form>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
