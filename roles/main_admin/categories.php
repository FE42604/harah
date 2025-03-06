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

// Get creator's information for logging
$creator_query = "SELECT first_name, last_name FROM tbl_employee WHERE employee_id = ?";
$stmt = $conn->prepare($creator_query);
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$creator_result = $stmt->get_result();
$creator = $creator_result->fetch_assoc();
$creator_name = $creator['first_name'] . ' ' . $creator['last_name'];

// Handle category operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $conn->begin_transaction();
        
        try {
            switch ($_POST['action']) {
                case 'add':
                    $name = trim($_POST['category_name']);
                    $description = trim($_POST['category_description']);
                    
                    // Check if category already exists
                    $check = $conn->prepare("SELECT category_id FROM tbl_categories WHERE category_name = ?");
                    $check->bind_param("s", $name);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        throw new Exception("Category name already exists");
                    }
                    
                    // Add new category
                    $stmt = $conn->prepare("INSERT INTO tbl_categories (category_name, category_description) VALUES (?, ?)");
                    $stmt->bind_param("ss", $name, $description);
                    
                    if ($stmt->execute()) {
                        // Log the action
                        $description = "Created new category: {$name} (by {$creator_name})";
                        $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) VALUES ('create_category', ?, NOW())");
                        $stmt->bind_param("s", $description);
                        $stmt->execute();
                        
                        $conn->commit();
                        $message = "Category added successfully";
                    } else {
                        throw new Exception("Error adding category");
                    }
                    break;

                case 'edit':
                    $id = $_POST['category_id'];
                    $name = trim($_POST['category_name']);
                    $description = trim($_POST['category_description']);
                    
                    // Check if new name already exists for other categories
                    $check = $conn->prepare("SELECT category_id FROM tbl_categories WHERE category_name = ? AND category_id != ?");
                    $check->bind_param("si", $name, $id);
                    $check->execute();
                    if ($check->get_result()->num_rows > 0) {
                        throw new Exception("Category name already exists");
                    }
                    
                    // Update category
                    $stmt = $conn->prepare("UPDATE tbl_categories SET category_name = ?, category_description = ? WHERE category_id = ?");
                    $stmt->bind_param("ssi", $name, $description, $id);
                    
                    if ($stmt->execute()) {
                        // Log the action
                        $description = "Updated category: {$name} (by {$creator_name})";
                        $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) VALUES ('update_category', ?, NOW())");
                        $stmt->bind_param("s", $description);
                        $stmt->execute();
                        
                        $conn->commit();
                        $message = "Category updated successfully";
                    } else {
                        throw new Exception("Error updating category");
                    }
                    break;

                case 'delete':
                    $id = $_POST['category_id'];
                    
                    // Check if category is in use
                    $check_products = $conn->prepare("SELECT COUNT(*) as count FROM tbl_products WHERE category_id = ?");
                    $check_products->bind_param("i", $id);
                    $check_products->execute();
                    $product_count = $check_products->get_result()->fetch_assoc()['count'];
                    
                    $check_ingredients = $conn->prepare("SELECT COUNT(*) as count FROM tbl_raw_ingredients WHERE category_id = ?");
                    $check_ingredients->bind_param("i", $id);
                    $check_ingredients->execute();
                    $ingredient_count = $check_ingredients->get_result()->fetch_assoc()['count'];
                    
                    if ($product_count > 0 || $ingredient_count > 0) {
                        throw new Exception("Cannot delete category: It is still in use by products or ingredients");
                    }
                    
                    // Get category name for logging
                    $get_name = $conn->prepare("SELECT category_name FROM tbl_categories WHERE category_id = ?");
                    $get_name->bind_param("i", $id);
                    $get_name->execute();
                    $category_name = $get_name->get_result()->fetch_assoc()['category_name'];
                    
                    // Delete category
                    $stmt = $conn->prepare("DELETE FROM tbl_categories WHERE category_id = ?");
                    $stmt->bind_param("i", $id);
                    
                    if ($stmt->execute()) {
                        // Log the action
                        $description = "Deleted category: {$category_name} (by {$creator_name})";
                        $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) VALUES ('delete_category', ?, NOW())");
                        $stmt->bind_param("s", $description);
                        $stmt->execute();
                        
                        $conn->commit();
                        $message = "Category deleted successfully";
                    } else {
                        throw new Exception("Error deleting category");
                    }
                    break;
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
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
            <h2 class="mb-4">Category Management</h2>
            
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
                        <h5 class="mb-0">Categories</h5>
                        <button type="button" class="btn btn-success btn-sm" data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                            <i class="bi bi-plus-lg"></i> Add Category
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Created At</th>
                                    <th>Last Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($category = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($category['category_name']); ?></td>
                                        <td><?php echo htmlspecialchars($category['category_description'] ?? 'N/A'); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($category['created_at'])); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($category['updated_at'])); ?></td>
                                        <td>
                                            <button type="button" class="btn btn-primary btn-sm" 
                                                    onclick="editCategory(<?php echo $category['category_id']; ?>, 
                                                                        '<?php echo addslashes($category['category_name']); ?>', 
                                                                        '<?php echo addslashes($category['category_description'] ?? ''); ?>')">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <button type="button" class="btn btn-danger btn-sm" 
                                                    onclick="deleteCategory(<?php echo $category['category_id']; ?>, 
                                                                          '<?php echo addslashes($category['category_name']); ?>')">
                                                <i class="bi bi-trash"></i> Delete
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

<!-- Add Category Modal -->
<div class="modal fade" id="addCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="category_description" name="category_description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade" id="editCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_category_description" class="form-label">Description</label>
                        <textarea class="form-control" id="edit_category_description" name="category_description" rows="3"></textarea>
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

<!-- Delete Category Modal -->
<div class="modal fade" id="deleteCategoryModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Delete Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="category_id" id="delete_category_id">
                    <p>Are you sure you want to delete the category "<span id="delete_category_name"></span>"?</p>
                    <p class="text-danger">This action cannot be undone if the category is not in use.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function editCategory(id, name, description) {
    document.getElementById('edit_category_id').value = id;
    document.getElementById('edit_category_name').value = name;
    document.getElementById('edit_category_description').value = description;
    new bootstrap.Modal(document.getElementById('editCategoryModal')).show();
}

function deleteCategory(id, name) {
    document.getElementById('delete_category_id').value = id;
    document.getElementById('delete_category_name').textContent = name;
    new bootstrap.Modal(document.getElementById('deleteCategoryModal')).show();
}
</script>

<?php require_once '../../includes/footer.php'; ?> 