<?php
session_start();
require_once "config/database.php";

// Protect the page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";

// Add customer
if (isset($_POST["add_customer"])) {

    $full_name = trim($_POST["full_name"]);
    $phone = trim($_POST["phone"]);
    $email = trim($_POST["email"]);

    $stmt = $conn->prepare(
        "INSERT INTO customers (full_name, phone, email)
         VALUES (?, ?, ?)"
    );

    $stmt->bind_param("sss", $full_name, $phone, $email);

    if ($stmt->execute()) {
        $message = "Customer added successfully!";
    } else {
        $message = "Error adding customer.";
    }

    $stmt->close();
}

// Delete customer
if (isset($_GET["delete"])) {

    $customer_id = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM customers WHERE customer_id = ?"
    );

    $stmt->bind_param("i", $customer_id);

    if ($stmt->execute()) {
        $message = "Customer deleted successfully!";
    } else {
        $message = "Unable to delete customer.";
    }

    $stmt->close();
}

// Retrieve customers
$result = $conn->query(
    "SELECT * FROM customers ORDER BY customer_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Customer Management</title>
</head>

<body>

<h1>Wi-Fi Sales Management System</h1>

<h2>Customer Management</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<h3>Add New Customer</h3>

<form method="POST">

    <label>Full Name:</label><br>
    <input type="text" name="full_name" required><br><br>

    <label>Phone Number:</label><br>
    <input type="text" name="phone" required><br><br>

    <label>Email Address:</label><br>
    <input type="email" name="email"><br><br>

    <button type="submit" name="add_customer">
        Add Customer
    </button>

</form>

<hr>

<h3>Registered Customers</h3>

<table border="1" cellpadding="10" cellspacing="0">

<tr>
    <th>ID</th>
    <th>Full Name</th>
    <th>Phone</th>
    <th>Email</th>
    <th>Date Registered</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()) { ?>

<tr>
    <td><?php echo $row["customer_id"]; ?></td>

    <td><?php echo htmlspecialchars($row["full_name"]); ?></td>

    <td><?php echo htmlspecialchars($row["phone"]); ?></td>

    <td><?php echo htmlspecialchars($row["email"] ?? ""); ?></td>

    <td><?php echo $row["created_at"]; ?></td>

    <td>
        <a href="customers.php?delete=<?php echo $row["customer_id"]; ?>"
           onclick="return confirm('Are you sure you want to delete this customer?')">
           Delete
        </a>
    </td>
</tr>

<?php } ?>

</table>

<br>

<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>