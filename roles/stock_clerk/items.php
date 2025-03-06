<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once 'functions/item-function.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

// Initialize variables
$success = $error = "";

// Fetch items with joined data from the database
$item_query = "SELECT i.*, 
               c.category_name,
               s.supplier_name,
               CONCAT(e.first_name, ' ', e.last_name) as employee_name
               FROM tbl_item_list i
               LEFT JOIN tbl_categories c ON i.category_id = c.category_id
               LEFT JOIN tbl_suppliers s ON i.supplier_id = s.supplier_id
               LEFT JOIN tbl_employee e ON i.employee_id = e.employee_id
               ORDER BY i.name";
$result = $conn->query($item_query);

if (!$result) {
    $error = "Failed to fetch items: " . $conn->error;
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Items Management</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createItemModal">
                <i class="bi bi-plus-circle"></i> Add New Item
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
                    <h5 class="mb-0">Items List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Item Name</th>
                                    <th>Category</th>
                                    <th>Supplier</th>
                                    <th>Cost</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Created At</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($result && $result->num_rows > 0): ?>
                                    <?php while ($item = $result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['category_name']); ?></td>
                                            <td><?php echo htmlspecialchars($item['supplier_name']); ?></td>
                                            <td>₱<?php echo number_format($item['cost'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $item['status'] === 'Active' ? 'success' : 'danger'; ?>">
                                                    <?php echo htmlspecialchars($item['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($item['employee_name']); ?></td>
                                            <td><?php echo date('M d, Y', strtotime($item['date_created'])); ?></td>
                                            <td>
                                                <button type="button" 
                                                        class="btn btn-sm btn-info" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#viewItemModal<?php echo $item['item_id']; ?>">
                                                    <i class="bi bi-eye"></i> View
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-warning" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#editItemModal<?php echo $item['item_id']; ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-<?php echo $item['status'] === 'Active' ? 'danger' : 'success'; ?>"
                                                        onclick="toggleItemStatus(<?php echo $item['item_id']; ?>, '<?php echo $item['status']; ?>')">
                                                    <i class="bi bi-power"></i> <?php echo $item['status'] === 'Active' ? 'Deactivate' : 'Activate'; ?>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                <?php else: ?>
                                    <tr><td colspan="8" class="text-center">No items found.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'modals/item-modal.php'; ?>
<?php require_once '../../includes/footer.php'; ?>

<script>
function toggleItemStatus(itemId, currentStatus) {
    if (confirm('Are you sure you want to ' + (currentStatus === 'Active' ? 'deactivate' : 'activate') + ' this item?')) {
        window.location.href = 'functions/item-function.php?action=toggle_status&item_id=' + itemId;
    }
}
</script>

<?php $conn->close(); ?> 