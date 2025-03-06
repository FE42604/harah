<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get total raw ingredients
$total_ingredients = $conn->query("SELECT COUNT(*) as count FROM tbl_raw_ingredients")->fetch_assoc()['count'];

// Get low stock raw ingredients (less than minimum stock)
$low_stock_ingredients = $conn->query("SELECT COUNT(*) as count FROM tbl_raw_ingredients WHERE raw_stock_quantity <= raw_reorder_level")->fetch_assoc()['count'];

// Get out of stock raw ingredients
$out_of_stock_ingredients = $conn->query("SELECT COUNT(*) as count FROM tbl_raw_ingredients WHERE raw_stock_quantity = 0")->fetch_assoc()['count'];

// Get pending purchase orders
$pending_orders = $conn->query("SELECT COUNT(*) as count FROM tbl_purchase_order_list WHERE status IN ('ordered', 'partially_received')")->fetch_assoc()['count'];

// Get recent stock movements for raw ingredients
$recent_movements = $conn->query("
    SELECT 
        SUBSTRING_INDEX(transaction_description, ' for ', 1) as type,
        SUBSTRING_INDEX(SUBSTRING_INDEX(transaction_description, ': ', 1), ' for ', -1) as ingredient_name,
        SUBSTRING_INDEX(transaction_description, ': ', -1) as quantity,
        transaction_date as date_added
    FROM tbl_transaction_log
    WHERE transaction_type IN ('Stock Update', 'Purchase Order Receive')
    ORDER BY transaction_date DESC
    LIMIT 5
");

// Get low stock raw ingredients list
$low_stock_list = $conn->query("
    SELECT il.name as raw_name, ri.raw_stock_quantity, ri.raw_reorder_level, il.unit_of_measure
    FROM tbl_raw_ingredients ri
    JOIN tbl_item_list il ON ri.item_id = il.item_id
    WHERE ri.raw_stock_quantity <= ri.raw_reorder_level
    ORDER BY ri.raw_stock_quantity ASC
    LIMIT 5
");
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Stock Clerk Dashboard</h2>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Total Raw Ingredients</h6>
                            <h2 class="mt-2 mb-0"><?php echo $total_ingredients; ?></h2>
                        </div>
                        <div class="text-primary">
                            <i class="bi bi-egg-fried" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Low Stock Ingredients</h6>
                            <h2 class="mt-2 mb-0"><?php echo $low_stock_ingredients; ?></h2>
                        </div>
                        <div class="text-warning">
                            <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Out of Stock</h6>
                            <h2 class="mt-2 mb-0"><?php echo $out_of_stock_ingredients; ?></h2>
                        </div>
                        <div class="text-danger">
                            <i class="bi bi-x-circle" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Pending Orders</h6>
                            <h2 class="mt-2 mb-0"><?php echo $pending_orders; ?></h2>
                        </div>
                        <div class="text-info">
                            <i class="bi bi-cart-check" style="font-size: 2.5rem;"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Quick Actions</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <a href="raw_ingredients.php" class="btn btn-primary w-100">
                                <i class="bi bi-egg-fried me-2"></i> Raw Ingredients
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="purchase_orders.php" class="btn btn-success w-100">
                                <i class="bi bi-cart-plus me-2"></i> Create Order
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="receive_orders.php" class="btn btn-warning w-100">
                                <i class="bi bi-box-seam me-2"></i> Receive Orders
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="stock_alerts.php" class="btn btn-danger w-100">
                                <i class="bi bi-exclamation-triangle me-2"></i> Stock Alerts
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="stock_history.php" class="btn btn-info w-100">
                                <i class="bi bi-clock-history me-2"></i> Stock History
                            </a>
                        </div>
                        <div class="col-md-6">
                            <a href="categories.php" class="btn btn-secondary w-100">
                                <i class="bi bi-tags me-2"></i> Categories
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Low Stock Ingredients</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Ingredient</th>
                                    <th>Current Stock</th>
                                    <th>Minimum Stock</th>
                                    <th>Unit</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ingredient = $low_stock_list->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ingredient['raw_name']); ?></td>
                                        <td><?php echo $ingredient['raw_stock_quantity']; ?></td>
                                        <td><?php echo $ingredient['raw_reorder_level']; ?></td>
                                        <td><?php echo $ingredient['unit_of_measure']; ?></td>
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