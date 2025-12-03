<?php
session_start();
require "db.php";

// If no user is logged in, make them
if (!isset($_SESSION['username'])) {
    header("LOCATION: login.php");
    exit;
}

$current_user = $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Bank Operations</title>
</head>
<body>

    <p><a href="main.php">Back to Main Menu</a></p>

    <?php
    // Here, we can use PHP to display two different webpages given conditions from the main php
    // Accounts page displayed here for bank operations.
    if (isset($_POST["accounts"])) {
        echo "<h2>Your Accounts</h2>";
        
        $accounts = get_accounts($current_user);
        
        if (count($accounts) > 0) {
            echo "<table border='1'>"; // Border added to match the lab's webpage
            echo "<tr><th>Account</th><th>Balance</th></tr>";
            
            foreach ($accounts as $row) {
                echo "<tr>";
                echo "<td>" . htmlspecialchars($row[0]) . "</td>";
                echo "<td>$" . htmlspecialchars(number_format($row[1], 2)) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        } else {
            echo "<p>You have no accounts.</p>";
        }
    
    // 2. Transfer page displayed here for bank operation
    } elseif (isset($_POST["transfer"])) {
    ?>
        <h2>Make a Transfer</h2>
        <form method="POST" action="bankoperation.php">
            <p>
                <label for="from_account">From account:</label>
                <input type="text" id="from_account" name="from_account" required>
            </p>
            <p>
                <label for="to_account">To account:</label>
                <input type="text" id="to_account" name="to_account" required>
            </p>
            <p>
                <label for="amount">Amount:</label>
                <input type="number" id="amount" name="amount" step="0.01" min="0.01" required>
            </p>
            <p>
                <button type="submit" name="confirm" >Confirm</button>
            </p>
        </form>
    
    <?php
    // If Confirm was clicked, then process the transfer
    } elseif (isset($_POST["confirm"])) {
        echo "<h2>Transfer Status</h2>";
        
        $from = $_POST["from_account"];
        $to = $_POST["to_account"];
        $amount = $_POST["amount"];
        
        // Call the transfer function from db.php
        $result = transfer($from, $to, $amount, $current_user);
        
        // Display the result of the transfer
        echo "<p>" . htmlspecialchars($result) . "</p>";
        
        echo '<p><a href="bankoperation.php" onclick="document.getElementById(\'transferForm\').submit(); return false;">Make another transfer</a></p>';
        
    // No button press results in an error
    } else {
        echo "<h2>Bank Operations</h2>";
        echo "<p>Please use the main menu to select an operation.</p>";
    }
    ?>
    
    <!-- Hidden -->
    <form method="POST" action="bankoperation.php" id="transferForm">
        <input type="hidden" name="transfer" value="1">
    </form>

</body>
</html>