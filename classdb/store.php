<?php
require "db.php";
session_start();    

// Fetch all categories for dropdown
try {
    $dbh = connectDB();
    $stmt = $dbh->prepare("SELECT category_id, name FROM Category ORDER BY name");
    $stmt->execute();
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $dbh = null;
} catch (PDOException $e) {
    die("DB Error: " . $e->getMessage());
}

// Handle search form submission
$searchResults = [];
if (isset($_GET['search_category'])) {
    $category_id = intval($_GET['search_category']);
    try {
        $dbh = connectDB();
        $stmt = $dbh->prepare("
            SELECT * 
            FROM Product 
            WHERE category_id = :category_id AND discontinued = FALSE
        ");
        $stmt->bindParam(":category_id", $category_id, PDO::PARAM_INT);
        $stmt->execute();
        $searchResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $dbh = null;
    } catch (PDOException $e) {
        die("DB Error: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Main Page (Before Login)</title>
</head>
<body>
<h1>Welcome to Our Store</h1>

<!-- Login button -->
<p><a href="login.php">Login</a></p>

<!-- Category selection form -->
<form method="GET">
    <label for="category">Select a category:</label>
    <select name="search_category" id="category">
        <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['category_id'] ?>">
                <?= htmlspecialchars($cat['name']) ?>
            </option>
        <?php endforeach; ?>
    </select>
    <input type="submit" value="Search">
</form>

<!-- Display products if a category is selected -->
<?php if (!empty($searchResults)): ?>
    <h2>Products in selected category</h2>
    <ul>
        <?php foreach ($searchResults as $product): ?>
            <li>
                <h3><?= htmlspecialchars($product['name']) ?></h3>
                <p><?= htmlspecialchars($product['description']) ?></p>
                <p>Price: $<?= number_format($product['price'], 2) ?></p>
                <p>Stock: <?= $product['stock'] ?></p>
                <?php if ($product['image']): ?>
                    <img src="<?= htmlspecialchars($product['image']) ?>" alt="<?= htmlspecialchars($product['name']) ?>" width="150">
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>

</body>
</html>
