<?php
// Include database connection and helper functions
require "db.php";

// Show feedback to user
$message = "";

// Handle form submission
if (isset($_POST["submit"])) {
    // Collect form input and trim whitespace
    $username = trim($_POST["username"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];
    $first_name = trim($_POST["first_name"]);
    $last_name = trim($_POST["last_name"]);
    $shipping_address = trim($_POST["shipping_address"]);

    // Validation
    if (empty($username) || empty($email) || empty($password) || empty($first_name) || empty($last_name) || empty($shipping_address)) {
        $message = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) { // Check if passwords match
        $message = "Passwords do not match.";
    } else {
        try {
            // Connect to the database
            $dbh = connectDB();

            // Check if username or email already exists in the Customer table
            $cust_stmt = $dbh->prepare("SELECT COUNT(*) FROM Customer WHERE user = :username OR email = :email");
            $cust_stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $cust_stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $cust_stmt->execute();

            // Check if username or email already exists in the Employee table
            $emp_stmt = $dbh->prepare("SELECT COUNT(*) FROM Employee WHERE user = :username OR email = :email");
            $emp_stmt->bindParam(":username", $username, PDO::PARAM_STR);
            $emp_stmt->bindParam(":email", $email, PDO::PARAM_STR);
            $emp_stmt->execute();

            // If username or email already taken in Employee or Customer table
            if ($cust_stmt->fetchColumn() > 0 || $emp_stmt->fetchColumn() > 0) {
                $message = "Username or email already exists.";
            } else {    // Hash the password using SHA-256 before storing
                $hash = hash("sha256", $password);

                // Insert the new customer record into the database
                $cust_stmt = $dbh->prepare("INSERT INTO Customer (user, pass, first_name, last_name, email, shipping_address) 
                                        VALUES (:user, :pass, :first_name, :last_name, :email, :shipping_address)");
                $cust_stmt->bindParam(":user", $username, PDO::PARAM_STR);
                $cust_stmt->bindParam(":pass", $hash, PDO::PARAM_STR);
                $cust_stmt->bindParam(":first_name", $first_name, PDO::PARAM_STR);
                $cust_stmt->bindParam(":last_name", $last_name, PDO::PARAM_STR);
                $cust_stmt->bindParam(":email", $email, PDO::PARAM_STR);
                $cust_stmt->bindParam(":shipping_address", $shipping_address, PDO::PARAM_STR);
                $cust_stmt->execute();

                // Show success message with link to login page
                $message = "Registration successful! <a href='login.php'>Login here</a>.";
            }

            // Close the database connection
            $dbh = null;
        } catch (PDOException $e) {
            // Log database errors for debugging, show generic message to user
            error_log("DB Error in registration: " . $e->getMessage());
            $message = "An error occurred. Please try again.";
        }
    }
}

// Handle cancel button click
if (isset($_POST["cancel"])) {
    // Redirect user back to homepage or index page
    header("Location: index.php");
    exit();
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Registration</title>
</head>
<body>
<h2>Register New Customer</h2>

<!-- Display feedback message -->
<?php if (!empty($message)) echo "<p>$message</p>"; ?>

<!-- Registration form -->
<form method="POST">
    <!-- Customer first name -->
    <label>First Name:</label><br>
    <input type="text" name="first_name" required><br><br>

    <!-- Customer last name -->
    <label>Last Name:</label><br>
    <input type="text" name="last_name" required><br><br>

    <!-- Username for login -->
    <label>Username:</label><br>
    <input type="text" name="username" required><br><br>

    <!-- Customer email -->
    <label>Email:</label><br>
    <input type="email" name="email" required><br><br>

    <!-- Password and confirmation -->
    <label>Password:</label><br>
    <input type="password" name="password" required><br><br>

    <label>Confirm Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <!-- Shipping address field -->
    <label>Shipping Address:</label><br>
    <textarea name="shipping_address" required></textarea><br><br>

    <!-- Submit and Cancel buttons -->
    <input type="submit" name="submit" value="Register">
    <input type="submit" name="cancel" value="Cancel">
</form>
</body>
</html>
