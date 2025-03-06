<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Handle stock movements
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'in' || $_POST['action'] === 'out') {
        $ingredient_id = $_POST['ingredient_id'];
        $quantity = floatval($_POST['quantity']);
        
        // Get current stock
        $query = "SELECT raw_stock_quantity, raw_name FROM tbl_raw_ingredients WHERE raw_ingredient_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("i", $ingredient_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $ingredient = $result->fetch_assoc();
        
        if ($_POST['action'] === 'out' && $quantity > $ingredient['raw_stock_quantity']) {
            $error = "Cannot remove more stock than available.";
        } else {
            // Calculate new quantity
            $new_quantity = $_POST['action'] === 'in' ? 
                          $ingredient['raw_stock_quantity'] + $quantity : 
                          $ingredient['raw_stock_quantity'] - $quantity;
            
            // Update stock
            $update_query = "UPDATE tbl_raw_ingredients SET raw_stock_quantity = ? WHERE raw_ingredient_id = ?";
            $update_stmt = $conn->prepare($update_query);
            $update_stmt->bind_param("di", $new_quantity, $ingredient_id);
            
            if ($update_stmt->execute()) {
                // Log the transaction
                $description = $_POST['action'] === 'in' ? 
                             "Stock in for {$ingredient['raw_name']}: +$quantity (by " . $_SESSION['full_name'] . ")" : 
                             "Stock out for {$ingredient['raw_name']}: -$quantity (by " . $_SESSION['full_name'] . ")";
                $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                            VALUES ('raw_ingredient_update', ?, NOW())";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("s", $description);
                $log_stmt->execute();
                
                $success = "Stock " . ($_POST['action'] === 'in' ? 'added' : 'removed') . " successfully.";
                // Use JavaScript redirect instead of header
                echo "<script>window.location.href = 'raw_ingredients.php';</script>";
                exit();
            } else {
                $error = "Error updating stock.";
            }
        }
    }
}

// Handle raw ingredient creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_ingredient') {
        $raw_name = $_POST['raw_name'];
        $raw_description = $_POST['raw_description'];
        $raw_unit_of_measure = $_POST['raw_unit_of_measure'];
        $raw_stock_quantity = $_POST['raw_stock_quantity'];
        $raw_cost_per_unit = $_POST['raw_cost_per_unit'];
        $raw_reorder_level = $_POST['raw_reorder_level'];
        $category_id = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
        
        // Insert new raw ingredient
        $query = "INSERT INTO tbl_raw_ingredients (raw_name, raw_description, raw_unit_of_measure, 
                  raw_stock_quantity, raw_cost_per_unit, raw_reorder_level, category_id, raw_created_at) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssddii", $raw_name, $raw_description, $raw_unit_of_measure, 
                         $raw_stock_quantity, $raw_cost_per_unit, $raw_reorder_level, $category_id);
        
        if ($stmt->execute()) {
            // Log the transaction
            $description = "Added new raw ingredient: $raw_name (by " . $_SESSION['full_name'] . ")";
            $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                        VALUES ('raw_ingredient_create', ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            $success = "Raw ingredient added successfully.";
            // Use JavaScript redirect instead of header
            echo "<script>window.location.href = 'raw_ingredients.php';</script>";
            exit();
        } else {
            $error = "Error adding raw ingredient.";
        }
    }
}

// Get all raw ingredients with their categories
$query = "SELECT r.*, c.category_name 
          FROM tbl_raw_ingredients r 
          LEFT JOIN tbl_categories c ON r.category_id = c.category_id 
          ORDER BY r.raw_name";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Raw Ingredients Management</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addIngredientModal">
                <i class="bi bi-plus-circle"></i> Add New Ingredient
            </button>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php endif; ?>
    
    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo $error; ?></div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Raw Ingredients List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Unit of Measure</th>
                                    <th>Stock Quantity</th>
                                    <th>Cost per Unit</th>
                                    <th>Reorder Level</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ingredient = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(isset($ingredient['category_name']) ? substr($ingredient['category_name'], 3) : 'Uncategorized'); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['raw_name']); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['raw_description']); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['raw_unit_of_measure']); ?></td>
                                        <td>
                                            <?php
                                            $stock_ratio = $ingredient['raw_stock_quantity'] / $ingredient['raw_reorder_level'];
                                            if ($ingredient['raw_stock_quantity'] == 0): ?>
                                                <span class="badge bg-dark">
                                                    <i class="bi bi-x-circle me-1"></i>
                                                    Out of Stock
                                                </span>
                                            <?php elseif ($ingredient['raw_stock_quantity'] <= $ingredient['raw_reorder_level']): ?>
                                                <span class="badge bg-danger">
                                                    <i class="bi bi-exclamation-circle me-1"></i>
                                                    <?php echo $ingredient['raw_stock_quantity']; ?>
                                                </span>
                                            <?php elseif ($stock_ratio <= 1.5): ?>
                                                <span class="badge bg-warning">
                                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                                    <?php echo $ingredient['raw_stock_quantity']; ?>
                                                </span>
                                            <?php else: ?>
                                                <span class="badge bg-success">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    <?php echo $ingredient['raw_stock_quantity']; ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td>₱<?php echo number_format($ingredient['raw_cost_per_unit'], 2); ?></td>
                                        <td><?php echo $ingredient['raw_reorder_level']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($ingredient['raw_created_at'])); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-success me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stockInModal<?php echo $ingredient['raw_ingredient_id']; ?>">
                                                <i class="bi bi-plus-circle"></i> Stock In
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-danger me-1" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#stockOutModal<?php echo $ingredient['raw_ingredient_id']; ?>">
                                                <i class="bi bi-dash-circle"></i> Stock Out
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewIngredientModal<?php echo $ingredient['raw_ingredient_id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Stock In Modal -->
                                    <div class="modal fade" id="stockInModal<?php echo $ingredient['raw_ingredient_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Stock In - <?php echo htmlspecialchars($ingredient['raw_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="in">
                                                        <input type="hidden" name="ingredient_id" value="<?php echo $ingredient['raw_ingredient_id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Quantity to Add</label>
                                                            <input type="number" step="0.01" class="form-control" name="quantity" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-success">Add Stock</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Stock Out Modal -->
                                    <div class="modal fade" id="stockOutModal<?php echo $ingredient['raw_ingredient_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Stock Out - <?php echo htmlspecialchars($ingredient['raw_name']); ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="action" value="out">
                                                        <input type="hidden" name="ingredient_id" value="<?php echo $ingredient['raw_ingredient_id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Quantity to Remove</label>
                                                            <input type="number" step="0.01" class="form-control" name="quantity" required>
                                                            <small class="text-muted">Current stock: <?php echo $ingredient['raw_stock_quantity']; ?></small>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" class="btn btn-danger">Remove Stock</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- View Ingredient Modal -->
                                    <div class="modal fade" id="viewIngredientModal<?php echo $ingredient['raw_ingredient_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Ingredient Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Category:</strong> <?php echo htmlspecialchars(isset($ingredient['category_name']) ? substr($ingredient['category_name'], 3) : 'Uncategorized'); ?></p>
                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($ingredient['raw_name']); ?></p>
                                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($ingredient['raw_description']); ?></p>
                                                    <p><strong>Unit of Measure:</strong> <?php echo htmlspecialchars($ingredient['raw_unit_of_measure']); ?></p>
                                                    <p><strong>Current Stock:</strong> <?php echo $ingredient['raw_stock_quantity']; ?></p>
                                                    <p><strong>Cost per Unit:</strong> ₱<?php echo number_format($ingredient['raw_cost_per_unit'], 2); ?></p>
                                                    <p><strong>Reorder Level:</strong> <?php echo $ingredient['raw_reorder_level']; ?></p>
                                                    <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($ingredient['raw_created_at'])); ?></p>
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

<!-- Add Ingredient Modal -->
<div class="modal fade" id="addIngredientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Raw Ingredient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_ingredient">
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">Select Category</option>
                            <?php
                            $categories_query = "SELECT * FROM tbl_categories WHERE category_name LIKE 'RI_%' ORDER BY category_name";
                            $categories_result = $conn->query($categories_query);
                            while ($category = $categories_result->fetch_assoc()) {
                                $display_name = substr($category['category_name'], 3);
                                echo "<option value='" . $category['category_id'] . "'>" . htmlspecialchars($display_name) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Ingredient Name</label>
                        <input type="text" class="form-control" name="raw_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="raw_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit of Measure</label>
                        <input type="text" class="form-control" name="raw_unit_of_measure" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Stock Quantity</label>
                        <input type="number" class="form-control" name="raw_stock_quantity" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cost per Unit</label>
                        <input type="number" class="form-control" name="raw_cost_per_unit" step="0.01" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reorder Level</label>
                        <input type="number" class="form-control" name="raw_reorder_level" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Ingredient</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 