<?php
session_start();
require "db.php"; // Include the database functions to make an actually functional login php site

if (isset($_POST["logout"])) {
    session_destroy();
    header("LOCATION: login.php");
    exit();
}

if (isset($_POST["login"])) {

    // $user = "Mary";
    // $pass = "Hello";

    // The new authenticate() method from db.php used here
    if (authenticate($_POST["username"], $_POST["password"]) == 1) {

        $_SESSION["username"] = $_POST["username"];
        header("LOCATION: main.php");
        exit();

    } else {
        $error = '<p style="color:red">Incorrect username and password</p>';
    }
}
?>

<head>
    <title>Login Page</title>
</head>
<body>
    <h3>Login here:</h3>

    <?php
    if (isset($error)) {
        echo $error;
    }
    ?>

    <form action="login.php" method="post">
        <div>
        <label for="username">username:</label>
        <input type="text" id="username" name="username">
    </div>
    <div>
        <label for="password">password:</label>
        <input type="password" id="password" name="password">
    </div>
    <div>
        <button type="submit" name="login">login</button>
    </div>
    </form>

</body>