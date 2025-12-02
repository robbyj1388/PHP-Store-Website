<?php
require "db.php";
session_start();

// Make sure the user is logged in
if (!isset($_SESSION["username"])) {
    header("Location: login.php");
    exit();
}

// Handle form submission
if (isset($_POST["submit"])) {
    $newPassword = $_POST["new_password"];
    $username = $_SESSION["username"];

    // Update the password/
    if (updatePassword($username, $newPassword)) {
        echo "<p>Password updated successfully!</p>";
        // Redirect to main page
        header("Location: emp_main.php");
        exit();   
    } else {
        echo "<p style='color:red'>Failed to update password. Try again.</p>";
    }
}
?>

<form method="POST">
    <label>Enter New Password:</label>
    <input type="password" name="new_password" required><br><br>
    <input type="submit" name="submit" value="Update Password">
</form>
