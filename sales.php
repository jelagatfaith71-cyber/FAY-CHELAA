<?php
session_start();
require_once "config/database.php";

// Protect the page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";

// RECORD A NEW SALE
if (isset($_POST["record_sale"])) {

    $customer_id = intval($_POST["customer_id"]);
    $package_id = intval($_POST["package_id"]);

    // Get the selected package price
    $package_stmt = $conn->prepare(
        "SELECT price FROM packages
         WHERE package_id = ? AND status = 'Active'"
    );

    $package_stmt->bind_param("i", $package_id);
    $package_stmt->execute();

    $package_result = $package_stmt->get_result();

    if ($package_result->num_rows == 1) {

        $package = $package_result->fetch_assoc();
        $amount = $package["price"];

        // Check that the customer exists
        $customer_stmt = $conn->prepare(
            "SELECT customer_id FROM customers
             WHERE customer_id = ?"
        );

        $customer_stmt->bind_param("i", $customer_id);
        $customer_stmt->execute();

        $customer_result = $customer_stmt->get_result();

        if ($customer_result->num_rows == 1) {

            $sale_date = date("Y-m-d");
            $status = "Pending";

            // Insert sale into database
            $stmt = $conn->prepare(
                "INSERT INTO sales
                (customer_id, package_id, sale_date, amount, status)
                VALUES (?, ?, ?, ?, ?)"
            );

            $stmt->bind_param(
                "iisds",
                $customer_id,
                $package_id,
                $sale_date,
                $amount,
                $status
            );

            if ($stmt->execute()) {
                $message = "Sale recorded successfully!";
            } else {
                $message = "Error recording sale: " . $stmt->error;
            }

            $stmt->close();

        } else {
            $message = "Selected customer does not exist.";
        }

        $customer_stmt->close();

    } else {
        $message = "Please select an active package.";
    }

    $package_stmt->close();
}

// GET CUSTOMERS
$customers = $conn->query(
    "SELECT customer_id, full_name FROM customers
     ORDER BY full_name ASC"
);

// GET ACTIVE PACKAGES
$packages = $conn->query(
    "SELECT package_id, package_name, price FROM packages
     WHERE status = 'Active'
     ORDER BY package_name ASC"
);

// DISPLAY SALES
$sales = $conn->query(
    "SELECT sales.sale_id,
            customers.full_name,
            packages.package_name,
            sales.sale_date,
            sales.amount,
            sales.status
     FROM sales
     INNER JOIN customers
     ON sales.customer_id = customers.customer_id
     INNER JOIN packages
     ON sales.package_id = packages.package_id
     ORDER BY sales.sale_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Sales Management</title>
</head>

<body>

<h1>Wi-Fi Sales Management System</h1>
<h2>Sales Management</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<h3>Record New Sale</h3>

<form method="POST">

    <label>Select Customer:</label><br>

    <select name="customer_id" required>
        <option value="">-- Select Customer --</option>

        <?php while ($customer = $customers->fetch_assoc()) { ?>
            <option value="<?php echo $customer["customer_id"]; ?>">
                <?php echo htmlspecialchars($customer["full_name"]); ?>
            </option>
        <?php } ?>

    </select>

    <br><br>

    <label>Select Wi-Fi Package:</label><br>

    <select name="package_id" required>
        <option value="">-- Select Package --</option>

        <?php while ($package = $packages->fetch_assoc()) { ?>
            <option value="<?php echo $package["package_id"]; ?>">
                <?php
                echo htmlspecialchars($package["package_name"])
                    . " - KSh "
                    . number_format($package["price"], 2);
                ?>
            </option>
        <?php } ?>

    </select>

    <br><br>

    <button type="submit" name="record_sale">
        Record Sale
    </button>

</form>

<hr>

<h3>Recorded Sales</h3>

<table border="1" cellpadding="10" cellspacing="0">

<tr>
    <th>Sale ID</th>
    <th>Customer</th>
    <th>Package</th>
    <th>Sale Date</th>
    <th>Amount (KSh)</th>
    <th>Status</th>
</tr>

<?php while ($row = $sales->fetch_assoc()) { ?>

<tr>
    <td><?php echo $row["sale_id"]; ?></td>

    <td><?php echo htmlspecialchars($row["full_name"]); ?></td>

    <td><?php echo htmlspecialchars($row["package_name"]); ?></td>

    <td><?php echo $row["sale_date"]; ?></td>

    <td><?php echo number_format($row["amount"], 2); ?></td>

    <td><?php echo htmlspecialchars($row["status"]); ?></td>
</tr>

<?php } ?>

</table>

<br>

<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>