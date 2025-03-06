<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Handle product creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_product') {
        $product_name = $_POST['product_name'];
        $category_id = $_POST['category_id'];
        $product_selling_price = $_POST['product_selling_price'];
        $product_quantity = $_POST['product_quantity'];
        $product_restock_qty = $_POST['product_restock_qty'];
        
        // Handle image upload
        $product_image = '';
        if (isset($_FILES['product_image']) && $_FILES['product_image']['error'] === 0) {
            $upload_dir = '../../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES['product_image']['name'], PATHINFO_EXTENSION));
            $product_image = uniqid() . '.' . $file_extension;
            move_uploaded_file($_FILES['product_image']['tmp_name'], $upload_dir . $product_image);
        }
        
        // Get employee name for logging
        $employee_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
        $emp_stmt = $conn->prepare($employee_query);
        $emp_stmt->bind_param("i", $_SESSION['employee_id']);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $employee = $emp_result->fetch_assoc();
        $employee_name = $employee['full_name'];
        
        // Insert new product
        $query = "INSERT INTO tbl_products (product_name, category_id, product_selling_price, 
                  product_quantity, product_restock_qty, product_image, product_created_at, employee_id) 
                  VALUES (?, ?, ?, ?, ?, ?, NOW(), ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siddiss", $product_name, $category_id, $product_selling_price, 
                         $product_quantity, $product_restock_qty, $product_image, $_SESSION['employee_id']);
        
        if ($stmt->execute()) {
            // Log the transaction
            $description = "Added new product: $product_name (by $employee_name)";
            $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                        VALUES ('product_create', ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            // Use JavaScript redirect instead of header
            echo "<script>window.location.href = 'products.php';</script>";
            exit();
        } else {
            $error = "Error adding product.";
        }
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
            <h2 class="mb-4">Products List</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addProductModal">
                <i class="bi bi-plus-circle"></i> Add New Product
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Products</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Product Name</th>
                                    <th>Image</th>
                                    <th>Selling Price</th>
                                    <th>Stock</th>
                                    <th>Restock Level</th>
                                    <th>Created At</th>
                                    <th>Employee</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($product = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($product['product_name']); ?></td>
                                        <td>
                                            <?php if ($product['product_image']): ?>
                                                <img src="../../uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                                     alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                     class="img-thumbnail"
                                                     style="max-width: 50px;">
                                            <?php else: ?>
                                                <span class="text-muted">No image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₱<?php echo number_format($product['product_selling_price'], 2); ?></td>
                                        <td><?php echo $product['product_quantity']; ?></td>
                                        <td><?php echo $product['product_restock_qty']; ?></td>
                                        <td><?php echo date('M d, Y h:i A', strtotime($product['product_created_at'])); ?></td>
                                        <td><?php echo htmlspecialchars($product['employee_name']); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewProductModal<?php echo $product['product_id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- View Product Modal -->
                                    <div class="modal fade" id="viewProductModal<?php echo $product['product_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Product Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="text-center mb-3">
                                                        <?php if ($product['product_image']): ?>
                                                            <img src="../../uploads/products/<?php echo htmlspecialchars($product['product_image']); ?>" 
                                                                 alt="<?php echo htmlspecialchars($product['product_name']); ?>"
                                                                 class="img-fluid"
                                                                 style="max-height: 200px;">
                                                        <?php endif; ?>
                                                    </div>
                                                    <p><strong>Product Name:</strong> <?php echo htmlspecialchars($product['product_name']); ?></p>
                                                    <p><strong>Category:</strong> <?php echo htmlspecialchars($product['category_name']); ?></p>
                                                    <p><strong>Selling Price:</strong> ₱<?php echo number_format($product['product_selling_price'], 2); ?></p>
                                                    <p><strong>Current Stock:</strong> <?php echo $product['product_quantity']; ?></p>
                                                    <p><strong>Restock Level:</strong> <?php echo $product['product_restock_qty']; ?></p>
                                                    <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($product['product_created_at'])); ?></p>
                                                    <p><strong>Created By:</strong> <?php echo htmlspecialchars($product['employee_name']); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_product">
                    <div class="mb-3">
                        <label class="form-label">Product Name</label>
                        <input type="text" class="form-control" name="product_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id" required>
                            <?php
                            $categories_query = "SELECT * FROM tbl_categories WHERE category_name NOT LIKE 'RI_%' ORDER BY category_name";
                            $categories_result = $conn->query($categories_query);
                            while ($category = $categories_result->fetch_assoc()) {
                                echo "<option value='" . $category['category_id'] . "'>" . htmlspecialchars($category['category_name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Selling Price</label>
                        <input type="number" class="form-control" name="product_selling_price" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Stock</label>
                        <input type="number" class="form-control" name="product_quantity" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Restock Level</label>
                        <input type="number" class="form-control" name="product_restock_qty" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Product Image</label>
                        <input type="file" class="form-control" name="product_image" accept="image/*">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Product</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php if (isset($success)): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<?php if (isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
<?php endif; ?>

<?php require_once '../../includes/footer.php'; ?> 