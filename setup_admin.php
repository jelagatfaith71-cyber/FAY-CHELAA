<?php

require_once "config/database.php";

$message = "";

$full_name = "System Administrator";
$username = "admin";
$password = "Admin@2027";
$role = "admin";

// Check whether the username already exists
$check = $conn->prepare(
    "SELECT user_id FROM users WHERE username = ?"
);

$check->bind_param("s", $username);
$check->execute();
$check->store_result();

if ($check->num_rows > 0) {

    $message = "Administrator account already exists.";

} else {

    // Hash the password
    $hashed_password = password_hash(
        $password,
        PASSWORD_DEFAULT
    );

    // Insert the administrator
    $stmt = $conn->prepare(
        "INSERT INTO users
        (full_name, username, password, role)
        VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param(
        "ssss",
        $full_name,
        $username,
        $hashed_password,
        $role
    );

    if ($stmt->execute()) {
        $message = "Administrator account created successfully.";
    } else {
        $message = "Unable to create administrator account.";
    }

    $stmt->close();
}

$check->close();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Administrator Setup</title>
</head>
<body>

    <h2>Wi-Fi Sales Management System</h2>

    <p><?php echo htmlspecialchars($message); ?></p>

    <p>Username: admin</p>
    <p>Temporary password: Admin@12345</p>

    <p>
        <a href="login.php">Proceed to Login</a>
    </p>

</body>
</html>