<?php

session_start();

require_once "config/database.php";

$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $username = trim($_POST["username"] ?? "");
    $password = $_POST["password"] ?? "";

    if ($username === "" || $password === "") {

        $error = "Please enter your username and password.";

    } else {

        $stmt = $conn->prepare(
            "SELECT user_id, full_name, username, password, role
             FROM users
             WHERE username = ?"
        );

        $stmt->bind_param("s", $username);
        $stmt->execute();

        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {

            if (password_verify(
                $password,
                $user["password"]
            )) {

                session_regenerate_id(true);

                $_SESSION["user_id"] = $user["user_id"];
                $_SESSION["full_name"] = $user["full_name"];
                $_SESSION["username"] = $user["username"];
                $_SESSION["role"] = $user["role"];

                header("Location: dashboard.php");
                exit;

            } else {

                $error = "Invalid username or password.";

            }

        } else {

            $error = "Invalid username or password.";

        }

        $stmt->close();
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - Wi-Fi Sales Management</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="login-container">

    <h1>Wi-Fi Sales Management</h1>

    <h2>Administrator Login</h2>

    <?php if ($error !== ""): ?>
        <p class="error">
            <?php echo htmlspecialchars($error); ?>
        </p>
    <?php endif; ?>

    <form method="POST" action="">

        <label>Username</label>

        <input
            type="text"
            name="username"
            required
            autocomplete="username"
        >

        <label>Password</label>

        <input
            type="password"
            name="password"
            required
            autocomplete="current-password"
        >

        <button type="submit">Login</button>

    </form>

</div>

</body>
</html>