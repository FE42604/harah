<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $category_name = $_POST['category_name'];
        $category_description = $_POST['category_description'];
        $is_raw_ingredient = isset($_POST['is_raw_ingredient']) ? true : false;
        
        // Add RI_ prefix if it's a raw ingredient category
        if ($is_raw_ingredient) {
            $category_name = 'RI_' . $category_name;
        }
        
        // Check if category already exists
        $check_query = "SELECT COUNT(*) as count FROM tbl_categories WHERE category_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $category_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error = "Category already exists.";
        } else {
            // Insert new category
            $query = "INSERT INTO tbl_categories (category_name, category_description, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $category_name, $category_description);
            
            if ($stmt->execute()) {
                // Get employee name for logging
                $employee_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
                $emp_stmt = $conn->prepare($employee_query);
                $emp_stmt->bind_param("i", $_SESSION['employee_id']);
                $emp_stmt->execute();
                $emp_result = $emp_stmt->get_result();
                $employee = $emp_result->fetch_assoc();
                $employee_name = $employee['full_name'];

                // Log the transaction
                $description = "Created new " . ($is_raw_ingredient ? "raw ingredient " : "") . "category: " . 
                             ($is_raw_ingredient ? substr($category_name, 3) : $category_name) . 
                             " By: " . $employee_name;
                $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                            VALUES ('category_create', ?, NOW())";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("s", $description);
                $log_stmt->execute();
                
                $success = "Category created successfully.";
            } else {
                $error = "Error creating category.";
            }
        }
    }
}

// Get all categories
$query = "SELECT * FROM tbl_categories ORDER BY category_name";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Categories Management</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                <i class="bi bi-plus-circle"></i> Add New Category
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
                    <h5 class="mb-0">Categories List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Type</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars(substr($category['category_name'], strpos($category['category_name'], 'RI_') === 0 ? 3 : 0)); ?></td>
                                        <td>
                                            <?php if (strpos($category['category_name'], 'RI_') === 0): ?>
                                                <span class="badge bg-info">Raw Ingredient</span>
                                            <?php else: ?>
                                                <span class="badge bg-primary">Product</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($category['category_description']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($category['created_at'])); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewCategoryModal<?php echo $category['category_id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- View Category Modal -->
                                    <div class="modal fade" id="viewCategoryModal<?php echo $category['category_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Category Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Category Name:</strong> <?php echo htmlspecialchars(substr($category['category_name'], strpos($category['category_name'], 'RI_') === 0 ? 3 : 0)); ?></p>
                                                    <p><strong>Type:</strong> <?php echo strpos($category['category_name'], 'RI_') === 0 ? 'Raw Ingredient' : 'Product'; ?></p>
                                                    <p><strong>Description:</strong> <?php echo htmlspecialchars($category['category_description']); ?></p>
                                                    <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($category['created_at'])); ?></p>
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

<!-- Create Category Modal -->
<div class="modal fade" id="createCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label">Category Name</label>
                        <input type="text" class="form-control" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="category_description" rows="3"></textarea>
                    </div>
                    <div class="mb-3 form-check form-switch">
                        <input class="form-check-input" type="checkbox" name="is_raw_ingredient" id="isRawIngredient">
                        <label class="form-check-label" for="isRawIngredient">Raw Ingredient</label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 