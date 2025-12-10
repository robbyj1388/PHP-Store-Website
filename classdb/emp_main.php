<?php
session_start();
require_once "db.php"; // Your PDO connection file

// Check if employee is logged in
if (!isset($_SESSION['employee_id'])) {
    die("You must be logged in as an employee to access this page.");
}

// Store employee ID from session
$employee = $_SESSION['employee_id'];

// Connect to database
try {
    $dbh = connectDB(); // Initialize PDO connection
} catch (PDOException $e) {
    die("DB Connection Error: " . $e->getMessage());
}

// Handle Restock Product
if (isset($_POST['restock'])) {
    $product_id = (int)$_POST['product_id'];
    $quantity = (int)$_POST['quantity'];

    // Get current stock
    $stmt = $dbh->prepare("SELECT stock FROM Product WHERE product_id = :id");
    $stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $current_stock = $stmt->fetchColumn();

    if ($current_stock !== false) {
        $new_stock = $current_stock + $quantity;

        // Update Product table
        $stmt = $dbh->prepare("
            UPDATE Product 
            SET stock = :stock, last_modified_by = :emp_id, last_modified_at = NOW() 
            WHERE product_id = :id
        ");
        $stmt->bindParam(":stock", $new_stock, PDO::PARAM_INT);
        $stmt->bindParam(":emp_id", $employee, PDO::PARAM_INT);
        $stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
        $stmt->execute();

        // Insert into ProductHistory
        $stmt = $dbh->prepare("
            INSERT INTO ProductHistory 
                (product_id, action_type, last_employee, old_stock, new_stock) 
            VALUES 
                (:pid, 'UPDATE', :emp_id, :old, :new)
        ");
        $stmt->bindParam(":pid", $product_id, PDO::PARAM_INT);
        $stmt->bindParam(":emp_id", $employee, PDO::PARAM_INT);
        $stmt->bindParam(":old", $current_stock, PDO::PARAM_INT);
        $stmt->bindParam(":new", $new_stock, PDO::PARAM_INT);
        $stmt->execute();

        echo "<p style='color:green'>Stock updated successfully.</p>";
    } else {
        echo "<p style='color:red'>Product not found.</p>";
    }
}

// Handle Change Product Price
if (isset($_POST['change_price'])) {
    $product_id = (int)$_POST['product_id'];
    $new_price = (float)$_POST['new_price'];

    // Get current price
    $stmt = $dbh->prepare("SELECT price FROM Product WHERE product_id = :id");
    $stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
    $stmt->execute();
    $old_price = $stmt->fetchColumn();

    if ($old_price !== false) {
        // Update Product price
        $stmt = $dbh->prepare("
            UPDATE Product 
            SET price = :price, last_modified_by = :emp_id, last_modified_at = NOW() 
            WHERE product_id = :id
        ");
        $stmt->bindParam(":price", $new_price);
        $stmt->bindParam(":emp_id", $employee, PDO::PARAM_INT);
        $stmt->bindParam(":id", $product_id, PDO::PARAM_INT);
        $stmt->execute();

        // Insert into ProductHistory
        $stmt = $dbh->prepare("
            INSERT INTO ProductHistory 
                (product_id, action_type, last_employee, old_price, new_price) 
            VALUES 
                (:pid, 'UPDATE', :emp_id, :old, :new)
        ");
        $stmt->bindParam(":pid", $product_id, PDO::PARAM_INT);
        $stmt->bindParam(":emp_id", $employee, PDO::PARAM_INT);
        $stmt->bindParam(":old", $old_price);
        $stmt->bindParam(":new", $new_price);
        $stmt->execute();

        echo "<p style='color:green'>Price updated successfully.</p>";
    } else {
        echo "<p style='color:red'>Product not found.</p>";
    }
}

// Fetch all products for dropdowns
try {
    $products = $dbh->query("SELECT product_id, name FROM Product")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}
?>

<!-- Employee Dashboard HTML -->
<h1>Employee Dashboard</h1>

<!-- Restock Product Form -->
<h2>Restock Product</h2>
<form method="post">
    <select name="product_id" required>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" name="quantity" placeholder="Quantity to add" required>
    <button type="submit" name="restock">Restock</button>
</form>

<!-- Change Product Price Form -->
<h2>Change Product Price</h2>
<form method="post">
    <select name="product_id" required>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <input type="number" step="0.01" name="new_price" placeholder="New price" min="0" required>
    <button type="submit" name="change_price">Change Price</button>
</form>

<!-- View Stock History Form -->
<h2>Stock History</h2>
<form method="get">
    <select name="stock_history_id" required>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">View Stock History</button>
</form>

<?php
// Display stock history
if (isset($_GET['stock_history_id'])) {
    $pid = (int)$_GET['stock_history_id'];
    $stmt = $dbh->prepare("
        SELECT timestamp, old_stock, new_stock, last_employee, last_customer 
        FROM ProductHistory 
        WHERE product_id = :pid 
        ORDER BY timestamp DESC
    ");
    $stmt->bindParam(":pid", $pid, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>
            <tr><th>Timestamp</th><th>Old Stock</th><th>New Stock</th><th>Modified By (Employee)</th><th>Customer</th></tr>";
    foreach ($history as $h) {
        echo "<tr>
                <td>{$h['timestamp']}</td>
                <td>{$h['old_stock']}</td>
                <td>{$h['new_stock']}</td>
                <td>{$h['last_employee']}</td>
                <td>{$h['last_customer']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- View Price History Form -->
<h2>Price History</h2>
<form method="get">
    <select name="price_history_id" required>
        <?php foreach ($products as $p): ?>
            <option value="<?= $p['product_id'] ?>"><?= htmlspecialchars($p['name']) ?></option>
        <?php endforeach; ?>
    </select>
    <button type="submit">View Price History</button>
</form>

<?php
// Display price history
if (isset($_GET['price_history_id'])) {
    $pid = (int)$_GET['price_history_id'];
    $stmt = $dbh->prepare("
        SELECT timestamp, old_price, new_price, last_employee 
        FROM ProductHistory 
        WHERE product_id = :pid 
        ORDER BY timestamp DESC
    ");
    $stmt->bindParam(":pid", $pid, PDO::PARAM_INT);
    $stmt->execute();
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "<table border='1'>
            <tr><th>Timestamp</th><th>Old Price</th><th>New Price</th><th>% Change</th><th>Modified By</th></tr>";
    foreach ($history as $h) {
        $percent = ($h['old_price'] != 0) ? round((($h['new_price'] - $h['old_price']) / $h['old_price']) * 100, 2) : 'N/A';
        echo "<tr>
                <td>{$h['timestamp']}</td>
                <td>{$h['old_price']}</td>
                <td>{$h['new_price']}</td>
                <td>{$percent}%</td>
                <td>{$h['last_employee']}</td>
              </tr>";
    }
    echo "</table>";
}
?>

<!-- Logout button -->
<form method="post" style="margin-top:20px;">
    <button type="submit" name="exit" style="padding:10px 18px; font-size:1rem;">Logout</button>
</form>

<?php
    // Required to log out of the employee account
    if (isset($_POST['exit'])) {
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit();
    }
?>
