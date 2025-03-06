<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Main Admin
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Main Admin') {
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

// Handle product edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_product'])) {
    $product_id = $_POST['product_id'];
    $product_name = $_POST['product_name'];
    $product_selling_price = $_POST['product_selling_price'];
    $product_restock_qty = $_POST['product_restock_qty'];
    $category_id = $_POST['category_id'];
    
    $conn->begin_transaction();
    
    try {
        // Update product details
        $stmt = $conn->prepare("UPDATE tbl_products SET product_name = ?, product_selling_price = ?, product_restock_qty = ?, category_id = ? WHERE product_id = ?");
        $stmt->bind_param("sdiis", $product_name, $product_selling_price, $product_restock_qty, $category_id, $product_id);
        $stmt->execute();
        
        // Log transaction
        $description = "Product updated: {$product_name} (by " . $_SESSION['full_name'] . ")";
        $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description) VALUES ('Product Update', ?)");
        $stmt->bind_param("s", $description);
        $stmt->execute();
        
        $conn->commit();
        $message = "Product updated successfully.";
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}

// Handle product delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_product'])) {
    $product_id = $_POST['product_id'];
    
    $conn->begin_transaction();
    
    try {
        // Get product name for logging
        $stmt = $conn->prepare("SELECT product_name FROM tbl_products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        $product = $stmt->get_result()->fetch_assoc();
        
        // Delete product
        $stmt = $conn->prepare("DELETE FROM tbl_products WHERE product_id = ?");
        $stmt->bind_param("i", $product_id);
        $stmt->execute();
        
        // Log transaction
        $description = "Product deleted: {$product['product_name']} (by " . $_SESSION['full_name'] . ")";
        $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description) VALUES ('Product Delete', ?)");
        $stmt->bind_param("s", $description);
        $stmt->execute();
        
        $conn->commit();
        $message = "Product deleted successfully.";
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

// Get all categories for the edit form
$categories_query = "SELECT * FROM tbl_categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);
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
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#editModal<?php echo $product['product_id']; ?>">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal<?php echo $product['product_id']; ?>">
                                                <i class="bi bi-trash"></i> Delete
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

                                            <!-- Edit Modal -->
                                            <div class="modal fade" id="editModal<?php echo $product['product_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Edit Product - <?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <form method="POST" action="">
                                                            <div class="modal-body">
                                                                <input type="hidden" name="edit_product" value="1">
                                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                                <div class="mb-3">
                                                                    <label class="form-label">Product Name</label>
                                                                    <input type="text" class="form-control" name="product_name" value="<?php echo htmlspecialchars($product['product_name']); ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Selling Price</label>
                                                                    <input type="number" class="form-control" name="product_selling_price" value="<?php echo $product['product_selling_price']; ?>" step="0.01" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Restock Quantity</label>
                                                                    <input type="number" class="form-control" name="product_restock_qty" value="<?php echo $product['product_restock_qty']; ?>" required>
                                                                </div>
                                                                <div class="mb-3">
                                                                    <label class="form-label">Category</label>
                                                                    <select class="form-select" name="category_id" required>
                                                                        <?php while ($category = $categories_result->fetch_assoc()): ?>
                                                                            <option value="<?php echo $category['category_id']; ?>" <?php echo $category['category_id'] == $product['category_id'] ? 'selected' : ''; ?>>
                                                                                <?php echo htmlspecialchars($category['category_name']); ?>
                                                                            </option>
                                                                        <?php endwhile; ?>
                                                                    </select>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-primary">Save Changes</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Delete Modal -->
                                            <div class="modal fade" id="deleteModal<?php echo $product['product_id']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Delete Product - <?php echo htmlspecialchars($product['product_name']); ?></h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to delete this product? This action cannot be undone.</p>
                                                            <p class="text-danger"><i class="bi bi-exclamation-triangle"></i> Warning: This will permanently remove the product from the inventory.</p>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <form method="POST" action="">
                                                                <input type="hidden" name="delete_product" value="1">
                                                                <input type="hidden" name="product_id" value="<?php echo $product['product_id']; ?>">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                <button type="submit" class="btn btn-danger">Delete Product</button>
                                                            </form>
                                                        </div>
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