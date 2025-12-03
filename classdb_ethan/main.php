<?php
session_start();

if (!isset($_SESSION["username"])) {
    header("LOCATION: login.php");
    exit();
}

$username = $_SESSION["username"];
?>

<head>
    <title>Online Minibank</title>
</head>
<body>

    <?php
        // htmlspecialchars was implimented to 
        echo '<p align="right"> Welcome ' . htmlspecialchars($username) . '</p>'; 
    ?>

    <form action="login.php" method="post">
        <p align="right">
        <input type="submit" value="logout" name="logout">
        </p>
    </form>

    <div>
        <p>Welcome to our online minibank!</p>
        <p>We can help you to transfer the money or display your accounts.</p>
        <p>What would you like to do? Please click one of the buttons</p>

        <form action="bankoperation.php" method="post">
            <button type="submit" name="transfer">Transfer</button>
            <button type="submit" name="accounts">Accounts</button>
        </form>
    </div>
</body>
</html>