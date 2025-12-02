<html>
    <body>
        <form method="POST" action="login.php">
            <label>Username:</label>
            <input type="text" name="username"><br>
            <label>Password:</label>
            <input type="password" name="password"><br>
            <input type="submit" name="login" value="Login">
            <input type="submit" name="register" value="Create an Account">
        </form>

        <?php
        require "db.php";
        session_start();

        // Destroy session on logout
        if (isset($_POST["logout"])) {
            session_destroy();
        }

        // User clicked register button
        if (isset($_POST["register"])){
            header("Location: register.php");
            exit();
        }

        // User clicked the login button
        if (isset($_POST["login"])) {
            $username = $_POST["username"];
            $password = $_POST["password"];

            // Use the new authenticate() function
            $auth = authenticate($username, $password);

            if ($auth['type'] == 1) {  // Employee
                $_SESSION["employee_id"] = $auth['id'];  // store employee ID
                // Redirect to employee main page
                header("Location: emp_main.php");
                exit();

            } elseif ($auth['type'] == 2) {  // Customer
                $_SESSION["customer_id"] = $auth['id']; // store customer ID
                // Redirect to customer main page
                header("Location: store.php");
                exit();

            } else {  // Invalid login
                echo '<p style="color:red">Incorrect username or password</p>';
            }
        }
        ?>
    </body>
</html>
