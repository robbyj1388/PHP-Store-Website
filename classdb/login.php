<?php
require "db.php";
session_start();

// LOGOUT HANDLING 
// Handle logout
if (isset($_POST["logout"])) {
    // Destroy all session data
    session_unset();
    session_destroy();

    // Redirect to store.php as a non-logged-in user
    header("Location: store.php");
    exit();
}

// REGISTRATION
if (isset($_POST["register"])) {
    header("Location: register.php");
    exit();
}

// LOGIN HANDLING
if (isset($_POST["login"])) {
    $username = $_POST["username"];
    $password = $_POST["password"];

    $auth = authenticate($username, $password);

    // Employee
    if ($auth['type'] == 1) {
        $_SESSION["employee_id"] = $auth['id'];
        $_SESSION["username"] = $username;

        // Check if the employee must update their password
        if (needsPasswordReset($username)) {
            header("Location: reset_password.php");
            exit();
        }

        header("Location: emp_main.php");
        exit();

    } elseif ($auth['type'] == 2) {  // Customer
        $_SESSION["customer_id"] = $auth['id'];
        header("Location: store.php");
        exit();

    // Invalid login
    } else {
        $error = "Incorrect username or password";
    }
}
?>

<!-- HTML BELOW THIS LINE -->
<html>
<body>
    <h1>Login Here </h1>

<form method="POST" action="login.php">
    <label>Username:</label>
    <input type="text" name="username"><br>

    <label>Password:</label>
    <input type="password" name="password"><br>

    <input type="submit" name="login" value="Login">
    <input type="submit" name="register" value="Create an Account">
</form>

<?php if (isset($error)): ?>
    <p style="color:red"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

</body>
</html>
