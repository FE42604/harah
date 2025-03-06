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
        $query = "SELECT ri.raw_stock_quantity, il.name as raw_name 
                 FROM tbl_raw_ingredients ri 
                 JOIN tbl_item_list il ON ri.item_id = il.item_id 
                 WHERE ri.raw_ingredient_id = ?";
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
        $item_id = $_POST['item_id'];
        $raw_description = $_POST['raw_description'];
        $raw_stock_quantity = $_POST['raw_stock_quantity'];
        $raw_cost_per_unit = $_POST['raw_cost_per_unit'];
        $raw_reorder_level = $_POST['raw_reorder_level'];
        
        // Get employee name for logging
        $employee_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
        $emp_stmt = $conn->prepare($employee_query);
        $emp_stmt->bind_param("i", $_SESSION['employee_id']);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $employee = $emp_result->fetch_assoc();
        $employee_name = $employee['full_name'];
        
        // Get item name for logging
        $item_query = "SELECT name FROM tbl_item_list WHERE item_id = ?";
        $item_stmt = $conn->prepare($item_query);
        $item_stmt->bind_param("i", $item_id);
        $item_stmt->execute();
        $item_result = $item_stmt->get_result();
        $item = $item_result->fetch_assoc();
        $item_name = $item['name'];
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            // Insert into tbl_raw_ingredients
            $raw_query = "INSERT INTO tbl_raw_ingredients (item_id, raw_description, raw_stock_quantity, 
                         raw_cost_per_unit, raw_reorder_level) 
                         VALUES (?, ?, ?, ?, ?)";
            $raw_stmt = $conn->prepare($raw_query);
            $raw_stmt->bind_param("isidi", $item_id, $raw_description, 
                                $raw_stock_quantity, $raw_cost_per_unit, $raw_reorder_level);
            $raw_stmt->execute();
            
            // Log the transaction
            $description = "Added new raw ingredient: $item_name (by $employee_name)";
            $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                        VALUES ('raw_ingredient_create', ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            $conn->commit();
            echo "<script>window.location.href = 'raw_ingredients.php';</script>";
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Error adding raw ingredient: " . $e->getMessage();
        }
    } elseif ($_POST['action'] === 'stock_movement') {
        $raw_ingredient_id = $_POST['raw_ingredient_id'];
        $quantity = $_POST['quantity'];
        $action = $_POST['action_type'];
        
        // Get employee name for logging
        $employee_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
        $emp_stmt = $conn->prepare($employee_query);
        $emp_stmt->bind_param("i", $_SESSION['employee_id']);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $employee = $emp_result->fetch_assoc();
        $employee_name = $employee['full_name'];
        
        // Get ingredient details
        $ingredient_query = "SELECT ri.*, il.name as raw_name, il.description as item_description,
                           il.unit_of_measure, il.supplier_id, il.employee_id,
                           c.category_name, s.supplier_name,
                           CONCAT(e.first_name, ' ', e.last_name) as employee_name
                           FROM tbl_raw_ingredients ri 
                           JOIN tbl_item_list il ON ri.item_id = il.item_id
                           LEFT JOIN tbl_categories c ON il.category_id = c.category_id 
                           LEFT JOIN tbl_suppliers s ON il.supplier_id = s.supplier_id
                           LEFT JOIN tbl_employee e ON il.employee_id = e.employee_id
                           WHERE ri.raw_ingredient_id = ?";
        $ing_stmt = $conn->prepare($ingredient_query);
        $ing_stmt->bind_param("i", $raw_ingredient_id);
        $ing_stmt->execute();
        $ing_result = $ing_stmt->get_result();
        $ingredient = $ing_result->fetch_assoc();
        
        // Start transaction
        $conn->begin_transaction();
        
        try {
            if ($action === 'in') {
                // Update stock quantity
                $update_query = "UPDATE tbl_raw_ingredients 
                               SET raw_stock_quantity = raw_stock_quantity + ?,
                                   raw_stock_in = raw_stock_in + ?
                               WHERE raw_ingredient_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("iii", $quantity, $quantity, $raw_ingredient_id);
                $update_stmt->execute();
                
                // Log the transaction
                $description = "Stock in for {$ingredient['raw_name']}: +$quantity (by $employee_name)";
            } else {
                // Check if enough stock
                if ($ingredient['raw_stock_quantity'] < $quantity) {
                    throw new Exception("Insufficient stock. Current stock: {$ingredient['raw_stock_quantity']}");
                }
                
                // Update stock quantity
                $update_query = "UPDATE tbl_raw_ingredients 
                               SET raw_stock_quantity = raw_stock_quantity - ?,
                                   raw_stock_out = raw_stock_out + ?
                               WHERE raw_ingredient_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param("iii", $quantity, $quantity, $raw_ingredient_id);
                $update_stmt->execute();
                
                // Log the transaction
                $description = "Stock out for {$ingredient['raw_name']}: -$quantity (by $employee_name)";
            }
            
            $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                        VALUES ('stock_movement', ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            $conn->commit();
            header("Location: raw_ingredients.php");
            exit();
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Get all raw ingredients with their categories and suppliers
$query = "SELECT ri.*, il.name as raw_name, il.description as item_description,
          il.unit_of_measure, il.supplier_id, il.employee_id,
          c.category_name, s.supplier_name,
          CONCAT(e.first_name, ' ', e.last_name) as employee_name
          FROM tbl_raw_ingredients ri 
          JOIN tbl_item_list il ON ri.item_id = il.item_id
          LEFT JOIN tbl_categories c ON il.category_id = c.category_id 
          LEFT JOIN tbl_suppliers s ON il.supplier_id = s.supplier_id
          LEFT JOIN tbl_employee e ON il.employee_id = e.employee_id
          WHERE c.category_name LIKE 'RI_%'
          ORDER BY il.name";
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
                                    <th>Item Name</th>
                                    <th>Description</th>
                                    <th>Unit of Measure</th>
                                    <th>Supplier</th>
                                    <th>Employee</th>
                                    <th>Category</th>
                                    <th>Cost</th>
                                    <th>Stock Quantity</th>
                                    <th>Reorder Level</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($ingredient = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ingredient['raw_name']); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['item_description']); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['unit_of_measure']); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($ingredient['employee_name']); ?></td>
                                        <td><?php echo htmlspecialchars(isset($ingredient['category_name']) ? substr($ingredient['category_name'], 3) : 'Uncategorized'); ?></td>
                                        <td>₱<?php echo number_format($ingredient['raw_cost_per_unit'], 2); ?></td>
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
                                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($ingredient['item_description']); ?></p>
                                                    <p><strong>Unit of Measure:</strong> <?php echo htmlspecialchars($ingredient['unit_of_measure']); ?></p>
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
                    <input type="hidden" name="item_id" id="item_id">
                    <input type="hidden" name="category_id" id="category_id">
                    <input type="hidden" name="supplier_id" id="supplier_id">
                    <div class="mb-3">
                        <label class="form-label">Ingredient Name</label>
                        <select class="form-select" name="item_id" id="ingredientSelect" required>
                            <option value="">Select Ingredient</option>
                            <?php
                            $items_query = "SELECT il.*, c.category_name, s.supplier_name 
                                          FROM tbl_item_list il 
                                          LEFT JOIN tbl_categories c ON il.category_id = c.category_id 
                                          LEFT JOIN tbl_suppliers s ON il.supplier_id = s.supplier_id 
                                          WHERE NOT EXISTS (
                                              SELECT 1 FROM tbl_raw_ingredients ri 
                                              WHERE ri.item_id = il.item_id
                                          )
                                          ORDER BY il.name";
                            $items_result = $conn->query($items_query);
                            while ($item = $items_result->fetch_assoc()) {
                                echo "<option value='" . $item['item_id'] . "' 
                                      data-description='" . htmlspecialchars($item['description']) . "'
                                      data-cost='" . $item['cost'] . "'
                                      data-category='" . $item['category_id'] . "'
                                      data-supplier='" . $item['supplier_id'] . "'
                                      data-unit='" . htmlspecialchars($item['unit_of_measure']) . "'
                                      data-name='" . htmlspecialchars($item['name']) . "'>" . 
                                      htmlspecialchars($item['name']) . "</option>";
                            }
                            ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="raw_description" id="description" rows="3" readonly></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Cost per Unit</label>
                        <input type="number" class="form-control" name="raw_cost_per_unit" id="cost" step="0.01" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <input type="text" class="form-control" id="category" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <input type="text" class="form-control" id="supplier" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit of Measure</label>
                        <input type="text" class="form-control" name="raw_unit_of_measure" id="unit" readonly>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Initial Stock Quantity</label>
                        <input type="number" class="form-control" name="raw_stock_quantity" required>
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

<style>
.form-control[readonly] {
    background-color: #e9ecef;
    cursor: not-allowed;
}

textarea[readonly] {
    background-color: #e9ecef;
    cursor: not-allowed;
}
</style>

<script>
document.getElementById('ingredientSelect').addEventListener('change', function() {
    const selectedOption = this.options[this.selectedIndex];
    if (selectedOption.value) {
        document.getElementById('description').value = selectedOption.dataset.description;
        document.getElementById('cost').value = selectedOption.dataset.cost;
        document.getElementById('category').value = selectedOption.dataset.category;
        document.getElementById('supplier').value = selectedOption.dataset.supplier;
        document.getElementById('unit').value = selectedOption.dataset.unit;
        document.getElementById('item_id').value = selectedOption.value;
        document.getElementById('category_id').value = selectedOption.dataset.category;
        document.getElementById('supplier_id').value = selectedOption.dataset.supplier;
    } else {
        document.getElementById('description').value = '';
        document.getElementById('cost').value = '';
        document.getElementById('category').value = '';
        document.getElementById('supplier').value = '';
        document.getElementById('unit').value = '';
        document.getElementById('item_id').value = '';
        document.getElementById('category_id').value = '';
        document.getElementById('supplier_id').value = '';
    }
});
</script>

<?php require_once '../../includes/footer.php'; ?> 