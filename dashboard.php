<?php

session_start();

require_once "config/database.php";

if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit;
}

// Total customers
$result = $conn->query(
    "SELECT COUNT(*) AS total FROM customers"
);
$total_customers = $result->fetch_assoc()["total"];

// Active packages
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM packages
     WHERE status = 'active'"
);
$total_packages = $result->fetch_assoc()["total"];

// Paid sales
$result = $conn->query(
    "SELECT COUNT(*) AS total
     FROM sales
     WHERE status = 'paid'"
);
$total_sales = $result->fetch_assoc()["total"];

// Total payments received
$result = $conn->query(
    "SELECT COALESCE(SUM(amount), 0) AS total
     FROM payments"
);
$total_payments = $result->fetch_assoc()["total"];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Dashboard</title>

    <link rel="stylesheet" href="css/style.css">
</head>

<body>

<div class="navbar">

    <a href="dashboard.php">Dashboard</a>
    <a href="customers.php">Customers</a>
    <a href="packages.php">Packages</a>
    <a href="sales.php">Sales</a>
    <a href="payments.php">Payments</a>
    <a href="reports.php">Reports</a>
    <a href="logout.php">Logout</a>

</div>

<div class="container">

    <h1>Wi-Fi Sales Management Dashboard</h1>

    <p>
        Welcome,
        <?php echo htmlspecialchars($_SESSION["full_name"]); ?>!
    </p>

    <div class="card">
        <h3>Total Customers</h3>
        <p><?php echo $total_customers; ?></p>
    </div>

    <div class="card">
        <h3>Active Wi-Fi Packages</h3>
        <p><?php echo $total_packages; ?></p>
    </div>

    <div class="card">
        <h3>Paid Sales</h3>
        <p><?php echo $total_sales; ?></p>
    </div>

    <div class="card">
        <h3>Total Payments Received</h3>
        <p>KSh <?php echo number_format(
            (float) $total_payments,
            2
        ); ?></p>
    </div>

</div>

</body>
</html>