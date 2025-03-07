<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

$message = '';
$error = '';

// Handle stock movement
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $product_id = $_POST['product_id'];
    $quantity = $_POST['quantity'];
    $type = $_POST['type']; // 'in' or 'out'
    
    $conn->begin_transaction();
    
    try {
        // Get current product details
        $stmt = $conn->prepare("SELECT product_name, product_quantity, product_restock_qty FROM tbl_products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        // Calculate new quantity
        $new_quantity = $type === 'in' ?    
            $product['product_quantity'] + $quantity : 
            $product['product_quantity'] - $quantity;
        
        // Check if stock out would make quantity negative
        if ($type === 'out' && $new_quantity < 0) {
            throw new Exception("Insufficient stock for this operation");
        }
        
        // Update product quantity
        $stmt = $conn->prepare("UPDATE tbl_products SET product_quantity = ? WHERE product_id = ?");
        $stmt->bind_param("ii", $new_quantity, $product_id);
        $stmt->execute();
        
        // Log transaction
        $description = "Stock {$type} for {$product['product_name']}: {$quantity} units (by " . $_SESSION['full_name'] . ")";
        $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description) VALUES ('Stock Update', ?)");
        $stmt->bind_param("s", $description);
        $stmt->execute();
        
        $conn->commit();
        $message = "Stock updated successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Get all products with their categories
$query = "SELECT p.*, c.category_name, 
          CONCAT(e.first_name, ' ', e.last_name) as employee_name
          FROM tbl_products p 
          LEFT JOIN tbl_categories c ON p.category_id = c.category_id 
          LEFT JOIN tbl_employee e ON p.employee_id = e.employee_id
          ORDER BY p.product_name";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Inventory Management</h2>
            
            <?php if ($message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo $message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Products</h5>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Product Name</th>
                                    <th>Category</th>
                                    <th>Current Stock</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo $product['product_quantity']; ?></td>
                                        <td>
                                            <?php
                                            $status_class = $product['product_quantity'] == 0 ? 'bg-danger' : 
                                                ($product['product_quantity'] <= $product['product_restock_qty'] ? 'bg-warning' : 'bg-success');
                                            $status_text = $product['product_quantity'] == 0 ? 'Out of Stock' : 
                                                ($product['product_quantity'] <= $product['product_restock_qty'] ? 'Low Stock' : 'In Stock');
                                            ?>
                                            <span class="badge <?php echo $status_class; ?>">
                                                <?php echo $status_text; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stockInModal<?php echo $product['product_id']; ?>">
                                                <i class="bi bi-plus-circle"></i> Stock In
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stockOutModal<?php echo $product['product_id']; ?>">
                                                <i class="bi bi-dash-circle"></i> Stock Out
                                            </button>
                                            
                                            <!-- Stock In Modal -->
                                            <div class="modal fade" id="stockInModal<?php echo $product['product_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Stock In - <?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                                <input type="hidden" name="type" value="in">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Quantity</label>
                                                                    <input type="number" class="form-control" name="quantity" required min="1">
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-success">Confirm Stock In</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Stock Out Modal -->
                                            <div class="modal fade" id="stockOutModal<?php echo $product['product_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Stock Out - <?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="action" value="update">
                                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                                <input type="hidden" name="type" value="out">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Quantity</label>
                                                                    <input type="number" class="form-control" name="quantity" required min="1" max="<?php echo $product['product_quantity']; ?>">
                                                                    <small class="text-muted">Maximum available: <?php echo $product['product_quantity']; ?></small>
                                                                    <?php if ($product['product_quantity'] <= $product['product_restock_qty']): ?>
                                                                        <div class="alert alert-warning mt-2">
                                                                            <i class="bi bi-exclamation-triangle"></i> Stock is below restock quantity (<?php echo $product['product_restock_qty']; ?>)
                                                                        </div>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Confirm Stock Out</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
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