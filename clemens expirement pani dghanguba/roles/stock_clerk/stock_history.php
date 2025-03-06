<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Helper Functions
function getStockHistory($conn, $type_filter = '', $date_from = '', $date_to = '') {
    $query = "
        SELECT 
            SUBSTRING_INDEX(transaction_description, ' for ', 1) as type,
            SUBSTRING_INDEX(SUBSTRING_INDEX(transaction_description, ': ', 1), ' for ', -1) as product_name,
            SUBSTRING_INDEX(transaction_description, ': ', -1) as quantity,
            transaction_date
        FROM tbl_transaction_log
        WHERE transaction_type = 'stock_update'
    ";

    // Add filters
    if ($type_filter) {
        $query .= " AND SUBSTRING_INDEX(transaction_description, ' for ', 1) = '" . $conn->real_escape_string($type_filter) . "'";
    }
    if ($date_from) {
        $query .= " AND DATE(transaction_date) >= '" . $conn->real_escape_string($date_from) . "'";
    }
    if ($date_to) {
        $query .= " AND DATE(transaction_date) <= '" . $conn->real_escape_string($date_to) . "'";
    }

    $query .= " ORDER BY transaction_date DESC";
    return $conn->query($query);
}

function getStockHistoryByProduct($conn, $product_id, $date_from = '', $date_to = '') {
    $query = "
        SELECT 
            SUBSTRING_INDEX(transaction_description, ' for ', 1) as type,
            SUBSTRING_INDEX(transaction_description, ': ', -1) as quantity,
            transaction_date
        FROM tbl_transaction_log
        WHERE transaction_type = 'stock_update'
        AND transaction_description LIKE ?
    ";

    $product_name = getProductName($conn, $product_id);
    $search = "%{$product_name}%";

    if ($date_from) {
        $query .= " AND DATE(transaction_date) >= '" . $conn->real_escape_string($date_from) . "'";
    }
    if ($date_to) {
        $query .= " AND DATE(transaction_date) <= '" . $conn->real_escape_string($date_to) . "'";
    }

    $query .= " ORDER BY transaction_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    return $stmt->get_result();
}

function getProductName($conn, $product_id) {
    $query = "SELECT product_name FROM tbl_products WHERE product_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    return $product['product_name'];
}

function getStockHistoryByDateRange($conn, $start_date, $end_date) {
    $query = "
        SELECT 
            SUBSTRING_INDEX(transaction_description, ' for ', 1) as type,
            SUBSTRING_INDEX(SUBSTRING_INDEX(transaction_description, ': ', 1), ' for ', -1) as product_name,
            SUBSTRING_INDEX(transaction_description, ': ', -1) as quantity,
            transaction_date
        FROM tbl_transaction_log
        WHERE transaction_type = 'stock_update'
        AND DATE(transaction_date) BETWEEN ? AND ?
        ORDER BY transaction_date DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result();
}

function getStockHistoryByType($conn, $type) {
    $query = "
        SELECT 
            SUBSTRING_INDEX(transaction_description, ' for ', 1) as type,
            SUBSTRING_INDEX(SUBSTRING_INDEX(transaction_description, ': ', 1), ' for ', -1) as product_name,
            SUBSTRING_INDEX(transaction_description, ': ', -1) as quantity,
            transaction_date
        FROM tbl_transaction_log
        WHERE transaction_type = 'stock_update'
        AND SUBSTRING_INDEX(transaction_description, ' for ', 1) = ?
        ORDER BY transaction_date DESC
    ";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $type);
    $stmt->execute();
    return $stmt->get_result();
}

// Get filter parameters
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_from = isset($_GET['date_from']) ? $_GET['date_from'] : '';
$date_to = isset($_GET['date_to']) ? $_GET['date_to'] : '';

// Get stock history based on filters
$result = getStockHistory($conn, $type_filter, $date_from, $date_to);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Stock Movement History</h2>
        </div>
    </div>

    <!-- Filters -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Movement Type</label>
                            <select name="type" class="form-select">
                                <option value="">All Types</option>
                                <option value="Stock in" <?php echo $type_filter === 'Stock in' ? 'selected' : ''; ?>>Stock In</option>
                                <option value="Stock out" <?php echo $type_filter === 'Stock out' ? 'selected' : ''; ?>>Stock Out</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo $date_from; ?>">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo $date_to; ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary me-2">Filter</button>
                            <a href="stock_history.php" class="btn btn-secondary">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Stock Movement History Table -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Stock Movement Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Product</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($movement = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y H:i', strtotime($movement['transaction_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($movement['product_name']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $movement['type'] === 'Stock in' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $movement['type']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $movement['quantity']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 