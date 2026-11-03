<?php
session_start();
require_once "config/database.php";

// Protect the page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";

// RECORD PAYMENT
if (isset($_POST["record_payment"])) {

    $sale_id = intval($_POST["sale_id"]);
    $amount_paid = floatval($_POST["amount_paid"]);
    $payment_method = $_POST["payment_method"];
    $transaction_reference = trim($_POST["transaction_reference"]);

    // Get sale details
    $stmt = $conn->prepare(
        "SELECT amount FROM sales WHERE sale_id = ?"
    );

    $stmt->bind_param("i", $sale_id);
    $stmt->execute();

    $sale_result = $stmt->get_result();

    if ($sale_result->num_rows == 1 && $amount_paid > 0) {

        $sale = $sale_result->fetch_assoc();
        $sale_amount = floatval($sale["amount"]);

        // Calculate previous payments
        $paid_stmt = $conn->prepare(
            "SELECT COALESCE(SUM(amount_paid), 0) AS total_paid
             FROM payments WHERE sale_id = ?"
        );

        $paid_stmt->bind_param("i", $sale_id);
        $paid_stmt->execute();

        $paid_result = $paid_stmt->get_result();
        $paid_row = $paid_result->fetch_assoc();

        $total_paid = floatval($paid_row["total_paid"]);
        $balance = $sale_amount - $total_paid;

        if ($amount_paid > $balance) {

            $message = "Payment exceeds the outstanding balance.";

        } else {

            $payment_date = date("Y-m-d");

            // Save payment
            $insert = $conn->prepare(
                "INSERT INTO payments
                (sale_id, amount_paid, payment_method,
                 transaction_reference, payment_date)
                VALUES (?, ?, ?, ?, ?)"
            );

            $insert->bind_param(
                "idsss",
                $sale_id,
                $amount_paid,
                $payment_method,
                $transaction_reference,
                $payment_date
            );

            if ($insert->execute()) {

                $new_total = $total_paid + $amount_paid;

                $new_status = ($new_total >= $sale_amount)
                    ? "Paid" : "Pending";

                // Update sale payment status
                $update = $conn->prepare(
                    "UPDATE sales SET status = ? WHERE sale_id = ?"
                );

                $update->bind_param(
                    "si",
                    $new_status,
                    $sale_id
                );

                $update->execute();
                $update->close();

                $message = "Payment recorded successfully!";

            } else {
                $message = "Error recording payment: " . $insert->error;
            }

            $insert->close();
        }

        $paid_stmt->close();

    } else {
        $message = "Invalid sale or payment amount.";
    }

    $stmt->close();
}

// GET SALES WITH OUTSTANDING BALANCES
$sales = $conn->query(
    "SELECT s.sale_id, c.full_name, p.package_name,
            s.amount,
            COALESCE(SUM(pay.amount_paid), 0) AS total_paid
     FROM sales s
     INNER JOIN customers c ON s.customer_id = c.customer_id
     INNER JOIN packages p ON s.package_id = p.package_id
     LEFT JOIN payments pay ON s.sale_id = pay.sale_id
     GROUP BY s.sale_id, c.full_name, p.package_name, s.amount
     HAVING s.amount > total_paid
     ORDER BY s.sale_id DESC"
);

// GET ALL PAYMENTS
$payments = $conn->query(
    "SELECT pay.payment_id, c.full_name, p.package_name,
            pay.amount_paid, pay.payment_method,
            pay.transaction_reference, pay.payment_date
     FROM payments pay
     INNER JOIN sales s ON pay.sale_id = s.sale_id
     INNER JOIN customers c ON s.customer_id = c.customer_id
     INNER JOIN packages p ON s.package_id = p.package_id
     ORDER BY pay.payment_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Payments Management</title>
</head>

<body>

<h1>Wi-Fi Sales Management System</h1>
<h2>Payments Management</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<h3>Record Customer Payment</h3>

<form method="POST">

    <label>Select Sale:</label><br>

    <select name="sale_id" required>
        <option value="">-- Select Sale --</option>

        <?php while ($sale = $sales->fetch_assoc()) {

            $balance = $sale["amount"] - $sale["total_paid"];
        ?>

        <option value="<?php echo $sale["sale_id"]; ?>">
            <?php
            echo htmlspecialchars($sale["full_name"])
                . " - "
                . htmlspecialchars($sale["package_name"])
                . " - Balance: KSh "
                . number_format($balance, 2);
            ?>
        </option>

        <?php } ?>

    </select>

    <br><br>

    <label>Amount Paid (KSh):</label><br>
    <input type="number" name="amount_paid"
           min="0.01" step="0.01" required>

    <br><br>

    <label>Payment Method:</label><br>

    <select name="payment_method" required>
        <option value="">-- Select Method --</option>
        <option value="M-Pesa">M-Pesa</option>
        <option value="Cash">Cash</option>
    </select>

    <br><br>

    <label>Transaction Reference:</label><br>
    <input type="text" name="transaction_reference">

    <br><br>

    <button type="submit" name="record_payment">
        Record Payment
    </button>

</form>

<hr>

<h3>Payment Records</h3>

<table border="1" cellpadding="10" cellspacing="0">

<tr>
    <th>Payment ID</th>
    <th>Customer</th>
    <th>Package</th>
    <th>Amount Paid</th>
    <th>Payment Method</th>
    <th>Transaction Reference</th>
    <th>Payment Date</th>
</tr>

<?php while ($row = $payments->fetch_assoc()) { ?>

<tr>
    <td><?php echo $row["payment_id"]; ?></td>
    <td><?php echo htmlspecialchars($row["full_name"]); ?></td>
    <td><?php echo htmlspecialchars($row["package_name"]); ?></td>
    <td>KSh <?php echo number_format($row["amount_paid"], 2); ?></td>
    <td><?php echo htmlspecialchars($row["payment_method"]); ?></td>
    <td><?php echo htmlspecialchars($row["transaction_reference"] ?? ""); ?></td>
    <td><?php echo $row["payment_date"]; ?></td>
</tr>

<?php } ?>

</table>

<br>

<a href="dashboard.php">Back to Dashboard</a>

</body>
</html>