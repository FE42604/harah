<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once 'functions/category-function.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Initialize variables
$success = $error = "";

// Fetch categories from the database
$category_query = "SELECT * FROM tbl_categories ORDER BY category_name";
$result = $conn->query($category_query);

if (!$result) {
    $error = "Failed to fetch categories: " . $conn->error;
}
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

    <?php if (!empty($success)): ?>
        <div class="alert alert-success"><?php echo $success; ?></div>
    <?php elseif (!empty($error)): ?>
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
                                <?php if ($result && $result->num_rows > 0): ?>
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
                                <?php else: ?>
                                    <tr><td colspan="5" class="text-center">No categories found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'modals/category-modal.php'; ?>
<?php require_once '../../includes/footer.php'; ?>

<?php $conn->close(); ?>
