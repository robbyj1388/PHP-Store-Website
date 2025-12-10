<?php
require "db.php";
session_start();

// Determine logged-in user
if (isset($_SESSION["employee_id"])) {
    $user_id = $_SESSION["employee_id"];
    $user_type = "employee";
    $redirect_page = "emp_main.php";
} elseif (isset($_SESSION["customer_id"])) {
    $user_id = $_SESSION["customer_id"];
    $user_type = "customer";
    $redirect_page = "store.php";
} else {
    header("Location: login.php");
    exit();
}

// Function to verify old password
function verifyOldPassword($user_id, $oldPassword, $user_type) {
    $dbh = connectDB();
    if ($user_type === "employee") {
        $stmt = $dbh->prepare("SELECT pass FROM Employee WHERE employee_id = :id");
    } else {
        $stmt = $dbh->prepare("SELECT pass FROM Customer WHERE customer_id = :id");
    }
    $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
    $stmt->execute();
    $hash = $stmt->fetchColumn();
    $dbh = null;

    return hash("sha256", $oldPassword) === $hash;
}

// Function to update password
function updatePasswordById($user_id, $newPassword, $user_type) {
    $hash = hash("sha256", $newPassword);
    $dbh = connectDB();
    if ($user_type === "employee") {
        $stmt = $dbh->prepare("UPDATE Employee SET pass = :pass, must_update_password = 0 WHERE employee_id = :id");
    } else {
        $stmt = $dbh->prepare("UPDATE Customer SET pass = :pass WHERE customer_id = :id");
    }
    $stmt->bindParam(":pass", $hash, PDO::PARAM_STR);
    $stmt->bindParam(":id", $user_id, PDO::PARAM_INT);
    $result = $stmt->execute();
    $dbh = null;
    return $result;
}

// Handle form submission
$message = "";
if (isset($_POST["submit"])) {
    $oldPassword = $_POST["old_password"];
    $newPassword = $_POST["new_password"];
    $confirmPassword = $_POST["confirm_password"];

    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $message = "All fields are required.";
    } elseif ($newPassword !== $confirmPassword) {
        $message = "New passwords do not match.";
    } elseif (!verifyOldPassword($user_id, $oldPassword, $user_type)) {
        $message = "Old password is incorrect.";
    } else {
        if (updatePasswordById($user_id, $newPassword, $user_type)) {
            header("Location: $redirect_page");
            exit();
        } else {
            $message = "Failed to update password. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Change Password</title>
</head>
<body>
<h2>Change Password</h2>

<?php if (!empty($message)) echo "<p style='color:red'>$message</p>"; ?>

<form method="POST">
    <label>Old Password:</label><br>
    <input type="password" name="old_password" required><br><br>

    <label>New Password:</label><br>
    <input type="password" name="new_password" required><br><br>

    <label>Confirm New Password:</label><br>
    <input type="password" name="confirm_password" required><br><br>

    <input type="submit" name="submit" value="Update Password">
</form>

<p><a href="login.php">Cancel</a></p>
</body>
</html>
