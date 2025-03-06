<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_return'])) {
        $purchase_item_id = $_POST['purchase_item_id'];
        $quantity_returned = $_POST['quantity_returned'];
        $return_reason = $_POST['return_reason'];
        
        // Insert into return_list
        $stmt = $conn->prepare("INSERT INTO tbl_return_list (purchase_item_id, quantity_returned, return_reason, employee_id) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iisi", $purchase_item_id, $quantity_returned, $return_reason, $employee_id);
        $stmt->execute();

        // Get purchase order details for status update
        $stmt = $conn->prepare("SELECT purchase_order_id FROM tbl_purchase_items WHERE purchase_item_id = ?");
        $stmt->bind_param("i", $purchase_item_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $purchase_item = $result->fetch_assoc();
        $purchase_order_id = $purchase_item['purchase_order_id'];

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Created return for purchase order #" . $purchase_order_id . " (by " . $employee_name . ")";
        logTransaction($conn, 'return', $description);

        $success = "Return created successfully.";
    }

    if (isset($_POST['complete_return'])) {
        $return_id = $_POST['return_id'];
        
        // Get return details
        $return = getReturnDetails($conn, $return_id);
        
        // Update return status to completed
        $stmt = $conn->prepare("UPDATE tbl_return_list SET status = 1 WHERE return_id = ?");
        $stmt->bind_param("i", $return_id);
        $stmt->execute();

        // Update purchase_items quantity_received
        $stmt = $conn->prepare("UPDATE tbl_purchase_items SET quantity_received = quantity_received - ? WHERE purchase_item_id = ?");
        $stmt->bind_param("ii", $return['quantity_returned'], $return['purchase_item_id']);
        $stmt->execute();

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Completed return #" . $return_id . " for purchase order #" . $return['purchase_order_id'] . " (by " . $employee_name . ")";
        logTransaction($conn, 'return_complete', $description);

        $success = "Return completed successfully.";
    }

    if (isset($_POST['cancel_return'])) {
        $return_id = $_POST['return_id'];
        
        // Get return details
        $return = getReturnDetails($conn, $return_id);
        
        // Update return status to cancelled
        $stmt = $conn->prepare("UPDATE tbl_return_list SET status = 2 WHERE return_id = ?");
        $stmt->bind_param("i", $return_id);
        $stmt->execute();

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Cancelled return #" . $return_id . " for purchase order #" . $return['purchase_order_id'] . " (by " . $employee_name . ")";
        logTransaction($conn, 'return_cancel', $description);

        $success = "Return cancelled successfully.";
    }
}

// Fetch returns
$returns_query = "SELECT rl.*, pol.purchase_order_id, s.supplier_name, e.first_name, e.last_name, 
                        pi.quantity_ordered, pi.quantity_received,
                        il.name as item_name
                 FROM tbl_return_list rl
                 JOIN tbl_purchase_items pi ON rl.purchase_item_id = pi.purchase_item_id
                 JOIN tbl_purchase_order_list pol ON pi.purchase_order_id = pol.purchase_order_id
                 JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id
                 JOIN tbl_employee e ON rl.employee_id = e.employee_id
                 JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id
                 JOIN tbl_item_list il ON ri.item_id = il.item_id
                 ORDER BY rl.return_date DESC";
$returns_result = $conn->query($returns_query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Returns Management</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createReturnModal">
                <i class="bi bi-plus-circle"></i> Create New Return
            </button>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Returns List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Return ID</th>
                                    <th>Order ID</th>
                                    <th>Supplier</th>
                                    <th>Item</th>
                                    <th>Quantity Returned</th>
                                    <th>Reason</th>
                                    <th>Status</th>
                                    <th>Date</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($return = $returns_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $return['return_id']; ?></td>
                                        <td>#<?php echo $return['purchase_order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($return['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($return['item_name']); ?></td>
                                        <td><?php echo $return['quantity_returned']; ?></td>
                                        <td><?php echo htmlspecialchars($return['return_reason']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $return['status'] == 0 ? 'warning' : 
                                                    ($return['status'] == 1 ? 'success' : 'danger'); 
                                            ?>">
                                                <?php 
                                                    echo $return['status'] == 0 ? 'Pending' : 
                                                        ($return['status'] == 1 ? 'Completed' : 'Cancelled'); 
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($return['return_date'])); ?></td>
                                        <td><?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewReturnModal<?php echo $return['return_id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <?php if ($return['status'] == 0): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#completeReturnModal<?php echo $return['return_id']; ?>">
                                                    <i class="bi bi-check-circle"></i> Complete
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelReturnModal<?php echo $return['return_id']; ?>">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- View Return Modal -->
                                    <div class="modal fade" id="viewReturnModal<?php echo $return['return_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Return Details #<?php echo $return['return_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Order ID:</strong> #<?php echo $return['purchase_order_id']; ?></p>
                                                    <p><strong>Supplier:</strong> <?php echo htmlspecialchars($return['supplier_name']); ?></p>
                                                    <p><strong>Item:</strong> <?php echo htmlspecialchars($return['item_name']); ?></p>
                                                    <p><strong>Quantity Ordered:</strong> <?php echo $return['quantity_ordered']; ?></p>
                                                    <p><strong>Quantity Received:</strong> <?php echo $return['quantity_received']; ?></p>
                                                    <p><strong>Quantity Returned:</strong> <?php echo $return['quantity_returned']; ?></p>
                                                    <p><strong>Reason:</strong> <?php echo htmlspecialchars($return['return_reason']); ?></p>
                                                    <p><strong>Status:</strong> 
                                                        <span class="badge bg-<?php 
                                                            echo $return['status'] == 0 ? 'warning' : 
                                                                ($return['status'] == 1 ? 'success' : 'danger'); 
                                                        ?>">
                                                            <?php 
                                                                echo $return['status'] == 0 ? 'Pending' : 
                                                                    ($return['status'] == 1 ? 'Completed' : 'Cancelled'); 
                                                            ?>
                                                        </span>
                                                    </p>
                                                    <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($return['return_date'])); ?></p>
                                                    <p><strong>Created By:</strong> <?php echo htmlspecialchars($return['first_name'] . ' ' . $return['last_name']); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Complete Return Modal -->
                                    <div class="modal fade" id="completeReturnModal<?php echo $return['return_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Complete Return #<?php echo $return['return_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to complete this return?</p>
                                                    <p class="text-danger">This will update the received quantity for this item.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="return_id" value="<?php echo $return['return_id']; ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="complete_return" class="btn btn-success">Complete Return</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancel Return Modal -->
                                    <div class="modal fade" id="cancelReturnModal<?php echo $return['return_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cancel Return #<?php echo $return['return_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to cancel this return?</p>
                                                    <p class="text-danger">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="return_id" value="<?php echo $return['return_id']; ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="cancel_return" class="btn btn-danger">Cancel Return</button>
                                                    </form>
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

<!-- Create Return Modal -->
<div class="modal fade" id="createReturnModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Return</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Purchase Item</label>
                        <select name="purchase_item_id" class="form-select" required>
                            <option value="">Select Item</option>
                            <?php
                            $items_query = "SELECT pi.*, il.name, pol.purchase_order_id, s.supplier_name 
                                          FROM tbl_purchase_items pi 
                                          JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id 
                                          JOIN tbl_item_list il ON ri.item_id = il.item_id 
                                          JOIN tbl_purchase_order_list pol ON pi.purchase_order_id = pol.purchase_order_id 
                                          JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
                                          WHERE pi.quantity_received > 0";
                            $items_result = $conn->query($items_query);
                            while ($item = $items_result->fetch_assoc()):
                            ?>
                                <option value="<?php echo $item['purchase_item_id']; ?>">
                                    <?php echo htmlspecialchars($item['name'] . ' - Order #' . $item['purchase_order_id'] . ' (' . $item['supplier_name'] . ')'); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Quantity to Return</label>
                        <input type="number" name="quantity_returned" class="form-control" min="1" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Return Reason</label>
                        <textarea name="return_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_return" class="btn btn-primary">Create Return</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 