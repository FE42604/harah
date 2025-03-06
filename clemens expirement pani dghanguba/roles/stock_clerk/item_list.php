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

// Handle item creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_item') {
        $name = $_POST['name'];
        $description = $_POST['description'];
        $unit_of_measure = $_POST['unit_of_measure'];
        $supplier_id = $_POST['supplier_id'];
        $category_id = $_POST['category_id'];
        $cost = $_POST['cost'];
        $status = $_POST['status'];
        
        // Get employee name for logging
        $employee_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
        $emp_stmt = $conn->prepare($employee_query);
        $emp_stmt->bind_param("i", $_SESSION['employee_id']);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $employee = $emp_result->fetch_assoc();
        $employee_name = $employee['full_name'];
        
        // Insert into tbl_item_list
        $query = "INSERT INTO tbl_item_list (name, description, unit_of_measure, supplier_id, employee_id, category_id, cost, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssiiiss", $name, $description, $unit_of_measure, $supplier_id, $_SESSION['employee_id'], 
                         $category_id, $cost, $status);
        
        if ($stmt->execute()) {
            // Log the transaction
            $description = "Added new item: $name (by $employee_name)";
            $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                        VALUES ('item_create', ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            $message = "Item added successfully.";
        } else {
            $error = "Error adding item: " . $conn->error;
        }
    }
}

// Get all items with their related information
$query = "SELECT il.*, 
          s.supplier_name,
          c.category_name,
          CONCAT(e.first_name, ' ', e.last_name) as employee_name
          FROM tbl_item_list il
          LEFT JOIN tbl_suppliers s ON il.supplier_id = s.supplier_id
          LEFT JOIN tbl_categories c ON il.category_id = c.category_id
          LEFT JOIN tbl_employee e ON il.employee_id = e.employee_id
          WHERE NOT EXISTS (
              SELECT 1 FROM tbl_raw_ingredients ri 
              WHERE ri.item_id = il.item_id
          )
          ORDER BY il.date_created DESC";
$result = $conn->query($query);

// Get suppliers for dropdown
$suppliers_query = "SELECT * FROM tbl_suppliers ORDER BY supplier_name";
$suppliers_result = $conn->query($suppliers_query);

// Get categories for dropdown
$categories_query = "SELECT * FROM tbl_categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Item List</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addItemModal">
                <i class="bi bi-plus-circle"></i> Add New Item
            </button>
        </div>
    </div>

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
            <h5 class="mb-0">Items List</h5>
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
                            <th>Status</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($item = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($item['name']); ?></td>
                                <td><?php echo htmlspecialchars($item['description']); ?></td>
                                <td><?php echo htmlspecialchars($item['unit_of_measure']); ?></td>
                                <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['employee_name']); ?></td>
                                <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                <td>₱<?php echo number_format($item['cost'], 2); ?></td>
                                <td>
                                    <span class="badge <?php echo $item['status'] === 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                        <?php echo $item['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($item['date_created'])); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewItemModal<?php echo $item['item_id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>

                            <!-- View Item Modal -->
                            <div class="modal fade" id="viewItemModal<?php echo $item['item_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Item Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($item['name']); ?></p>
                                            <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                                            <p><strong>Supplier:</strong> <?php echo htmlspecialchars($item['supplier_name']); ?></p>
                                            <p><strong>Employee:</strong> <?php echo htmlspecialchars($item['employee_name']); ?></p>
                                            <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category_name']); ?></p>
                                            <p><strong>Cost:</strong> ₱<?php echo number_format($item['cost'], 2); ?></p>
                                            <p><strong>Status:</strong> 
                                                <span class="badge <?php echo $item['status'] === 'Active' ? 'bg-success' : 'bg-danger'; ?>">
                                                    <?php echo $item['status']; ?>
                                                </span>
                                            </p>
                                            <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($item['date_created'])); ?></p>
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

<!-- Add Item Modal -->
<div class="modal fade" id="addItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_item">
                    
                    <div class="mb-3">
                        <label class="form-label">Item Name</label>
                        <input type="text" class="form-control" name="name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Unit of Measure</label>
                        <select class="form-select" name="unit_of_measure" required>    
                            <option value="">Select Unit of Measure</option>
                            <option value="kg">Kilogram (kg)</option>
                            <option value="g">Gram (g)</option>
                            <option value="mg">Milligram (mg)</option>
                            <option value="l">Liter (L)</option>
                            <option value="ml">Milliliter (mL)</option>
                            <option value="pcs">Pieces (pcs)</option>
                            <option value="dz">Dozen (dz)</option>
                            <option value="box">Box</option>
                            <option value="pack">Pack</option>
                            <option value="set">Set</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Supplier</label>
                        <select class="form-select" name="supplier_id">
                            <option value="">Select Supplier</option>
                            <?php 
                            $suppliers_result->data_seek(0);
                            while ($supplier = $suppliers_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>">
                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category_id">
                            <option value="">Select Category</option>
                            <?php 
                            $categories_result->data_seek(0);
                            while ($category = $categories_result->fetch_assoc()): 
                            ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Cost</label>
                        <input type="number" class="form-control" name="cost" step="0.01" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select class="form-select" name="status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 