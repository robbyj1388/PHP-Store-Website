<html>
    <body>
        <?php
            session_start();
            if (!isset($_SESSION["username"])) {
                header("LOCATION:login.php");
                print($_SESSION["username"]);
            }else {
                echo '<p align="right"> Welcome '. $_SESSION["username"].'</p>';
        ?>
            <form action="main.php" method="POST">
            <p align="right">
            <input type="submit" value="logout" name="logout">
            </p>
            </form>

            <?php
                if (isset($_POST["logout"])) {
                    session_destroy();
                    header("LOCATION:login.php");
                }
            ?>
        <?php
            }
        ?>

        <p>Welcome to our online minibook!</p>
        <p>We can help you to transfer the money or to display your accounts</p>
        <p>What would you like to do? Please click one of the buttons</p>
        <form action="bankoperation.php" method="POST">
            <button type="submit" name="transfer" value="transfer">Transfer</button>
            <button type="submit" name="accounts" value="accounts">Accounts</button>
        </form>

    </body>
</html>
