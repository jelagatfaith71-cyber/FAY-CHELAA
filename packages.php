<?php
session_start();
require_once "config/database.php";

// Protect the page
if (!isset($_SESSION["user_id"])) {
    header("Location: login.php");
    exit();
}

$message = "";

// ADD PACKAGE
if (isset($_POST["add_package"])) {

    $package_name = trim($_POST["package_name"]);
    $description = trim($_POST["description"]);
    $duration = intval($_POST["duration"]);
    $price = floatval($_POST["price"]);

    $stmt = $conn->prepare(
        "INSERT INTO packages
        (package_name, description, duration, price, status)
        VALUES (?, ?, ?, ?, 'Active')"
    );

    $stmt->bind_param(
        "ssid",
        $package_name,
        $description,
        $duration,
        $price
    );

    if ($stmt->execute()) {
        $message = "Package added successfully!";
    } else {
        $message = "Error adding package: " . $stmt->error;
    }

    $stmt->close();
}

// UPDATE PACKAGE
if (isset($_POST["update_package"])) {

    $package_id = intval($_POST["package_id"]);
    $package_name = trim($_POST["package_name"]);
    $description = trim($_POST["description"]);
    $duration = intval($_POST["duration"]);
    $price = floatval($_POST["price"]);
    $status = $_POST["status"];

    $stmt = $conn->prepare(
        "UPDATE packages
         SET package_name=?, description=?,
             duration=?, price=?, status=?
         WHERE package_id=?"
    );

    $stmt->bind_param(
        "ssidsi",
        $package_name,
        $description,
        $duration,
        $price,
        $status,
        $package_id
    );

    if ($stmt->execute()) {
        $message = "Package updated successfully!";
    } else {
        $message = "Error updating package: " . $stmt->error;
    }

    $stmt->close();
}

// DELETE PACKAGE
if (isset($_GET["delete"])) {

    $package_id = intval($_GET["delete"]);

    $stmt = $conn->prepare(
        "DELETE FROM packages WHERE package_id=?"
    );

    $stmt->bind_param("i", $package_id);

    if ($stmt->execute()) {
        $message = "Package deleted successfully!";
    } else {
        $message = "Unable to delete package. It may be linked to an existing sale.";
    }

    $stmt->close();
}

// GET PACKAGE FOR EDITING
$edit_package = null;

if (isset($_GET["edit"])) {

    $package_id = intval($_GET["edit"]);

    $stmt = $conn->prepare(
        "SELECT * FROM packages WHERE package_id=?"
    );

    $stmt->bind_param("i", $package_id);
    $stmt->execute();

    $edit_package = $stmt->get_result()->fetch_assoc();

    $stmt->close();
}

// DISPLAY PACKAGES
$result = $conn->query(
    "SELECT * FROM packages ORDER BY package_id DESC"
);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Package Management</title>
</head>

<body>

<h1>Wi-Fi Sales Management System</h1>
<h2>Package Management</h2>

<p><?php echo htmlspecialchars($message); ?></p>

<h3>
    <?php echo $edit_package ? "Edit Package" : "Add New Package"; ?>
</h3>

<form method="POST">

    <?php if ($edit_package) { ?>
        <input type="hidden" name="package_id"
        value="<?php echo $edit_package["package_id"]; ?>">
    <?php } ?>

    <label>Package Name:</label><br>
    <input type="text" name="package_name"
    value="<?php echo htmlspecialchars($edit_package["package_name"] ?? ""); ?>"
    required><br><br>

    <label>Description:</label><br>
    <textarea name="description" required><?php
    echo htmlspecialchars($edit_package["description"] ?? "");
    ?></textarea><br><br>

    <label>Duration (Days):</label><br>
    <input type="number" name="duration" min="1"
    value="<?php echo $edit_package["duration"] ?? ""; ?>"
    required><br><br>

    <label>Price (KSh):</label><br>
    <input type="number" name="price" min="0" step="0.01"
    value="<?php echo $edit_package["price"] ?? ""; ?>"
    required><br><br>

    <?php if ($edit_package) { ?>

        <label>Status:</label><br>
        <select name="status">
            <option value="Active"
            <?php if ($edit_package["status"] == "Active") echo "selected"; ?>>
                Active
            </option>

            <option value="Inactive"
            <?php if ($edit_package["status"] == "Inactive") echo "selected"; ?>>
                Inactive
            </option>
        </select><br><br>

        <button type="submit" name="update_package">
            Update Package
        </button>

        <a href="packages.php">Cancel</a>

    <?php } else { ?>

        <button type="submit" name="add_package">
            Add Package
        </button>

    <?php } ?>

</form>

<hr>

<h3>Available Packages</h3>

<table border="1" cellpadding="10" cellspacing="0">

<tr>
    <th>ID</th>
    <th>Package Name</th>
    <th>Description</th>
    <th>Duration</th>
    <th>Price (KSh)</th>
    <th>Status</th>
    <th>Action</th>
</tr>

<?php while ($row = $result->fetch_assoc()) { ?>

<tr>
    <td><?php echo $row["package_id"]; ?></td>

    <td><?php echo htmlspecialchars($row["package_name"]); ?></td>

    <td><?php echo htmlspecialchars($row["description"]); ?></td>

    <td><?php echo $row["duration"]; ?> days</td>

    <td><?php echo number_format($row["price"], 2); ?></td>

    <td><?php echo htmlspecialchars($row["status"]); ?></td>

    <td>
        <a href="packages.php?edit=<?php echo $row["package_id"]; ?>">
            Edit
        </a> |

        <a href="packages.php?delete=<?php echo $row["package_id"]; ?>"
        onclick="return confirm('Are you sure you want to delete this package?')">
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