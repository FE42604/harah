<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get low stock products
$low_stock_query = "
    SELECT p.*, c.category_name
    FROM tbl_products p
    LEFT JOIN tbl_categories c ON p.category_id = c.category_id
    WHERE p.product_quantity <= p.product_restock_qty
    ORDER BY p.product_quantity ASC
";
$low_stock_result = $conn->query($low_stock_query);

// Get out of stock products
$out_of_stock_query = "
    SELECT p.*, c.category_name
    FROM tbl_products p
    LEFT JOIN tbl_categories c ON p.category_id = c.category_id
    WHERE p.product_quantity = 0
    ORDER BY p.product_name ASC
";
$out_of_stock_result = $conn->query($out_of_stock_query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Stock Alerts</h2>
        </div>
    </div>

    <!-- Low Stock Alerts -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-warning text-white">
                    <h5 class="mb-0">Low Stock Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Restock Quantity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $low_stock_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo $product['product_quantity']; ?></td>
                                        <td><?php echo $product['product_restock_qty']; ?></td>
                                        <td>
                                            <span class="badge bg-warning">
                                                Low Stock
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stockInModal<?php echo $product['product_id']; ?>">
                                                <i class="bi bi-plus-circle"></i> Stock In
                                            </button>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Out of Stock Alerts -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="mb-0">Out of Stock Items</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Restock Quantity</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $out_of_stock_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo $product['product_quantity']; ?></td>
                                        <td><?php echo $product['product_restock_qty']; ?></td>
                                        <td>
                                            <span class="badge bg-danger">
                                                Out of Stock
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stockInModal<?php echo $product['product_id']; ?>">
                                                <i class="bi bi-plus-circle"></i> Stock In
                                            </button>
                                        </td>
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