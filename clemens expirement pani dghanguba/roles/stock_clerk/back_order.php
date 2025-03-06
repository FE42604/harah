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
    if (isset($_POST['receive_back_order'])) {
        $back_order_id = $_POST['back_order_id'];
        $quantity_received = $_POST['quantity_received'];
        
        // Get back order details
        $back_order = getBackOrderDetails($conn, $back_order_id);
        
        // Update back order status
        $stmt = $conn->prepare("UPDATE tbl_back_order_list SET status = 1 WHERE back_order_id = ?");
        $stmt->bind_param("i", $back_order_id);
        $stmt->execute();

        // Update purchase_items back_ordered_quantity and quantity_received
        $stmt = $conn->prepare("UPDATE tbl_purchase_items SET 
                               back_ordered_quantity = back_ordered_quantity - ?,
                               quantity_received = quantity_received + ?
                               WHERE purchase_item_id = ?");
        $stmt->bind_param("iii", $quantity_received, $quantity_received, $back_order['purchase_item_id']);
        $stmt->execute();

        // Check if all items are received
        $stmt = $conn->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN back_ordered_quantity = 0 THEN 1 ELSE 0 END) as received 
                               FROM tbl_purchase_items WHERE purchase_order_id = ?");
        $stmt->bind_param("i", $back_order['purchase_order_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $counts = $result->fetch_assoc();

        // Update purchase order status if all items are received
        if ($counts['total'] == $counts['received']) {
            updatePurchaseOrderStatus($conn, $back_order['purchase_order_id'], 'received');
        }

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Received back order #" . $back_order_id . " for purchase order #" . $back_order['purchase_order_id'] . " (by " . $employee_name . ")";
        logTransaction($conn, 'back_order_receive', $description);

        $success = "Back order received successfully.";
    }

    if (isset($_POST['cancel_back_order'])) {
        $back_order_id = $_POST['back_order_id'];
        
        // Get back order details
        $back_order = getBackOrderDetails($conn, $back_order_id);
        
        // Update back order status to cancelled
        $stmt = $conn->prepare("UPDATE tbl_back_order_list SET status = 2 WHERE back_order_id = ?");
        $stmt->bind_param("i", $back_order_id);
        $stmt->execute();

        // Update purchase_items back_ordered_quantity
        $stmt = $conn->prepare("UPDATE tbl_purchase_items SET back_ordered_quantity = back_ordered_quantity - ? WHERE purchase_item_id = ?");
        $stmt->bind_param("ii", $back_order['quantity_back_ordered'], $back_order['purchase_item_id']);
        $stmt->execute();

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Cancelled back order #" . $back_order_id . " for purchase order #" . $back_order['purchase_order_id'] . " (by " . $employee_name . ")";
        logTransaction($conn, 'back_order_cancel', $description);

        $success = "Back order cancelled successfully.";
    }
}

// Fetch back orders
$back_orders_query = "SELECT bol.*, pol.purchase_order_id, s.supplier_name, il.name as item_name, e.first_name, e.last_name 
                      FROM tbl_back_order_list bol 
                      JOIN tbl_purchase_items pi ON bol.purchase_item_id = pi.purchase_item_id 
                      JOIN tbl_purchase_order_list pol ON pi.purchase_order_id = pol.purchase_order_id 
                      JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
                      JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id 
                      JOIN tbl_item_list il ON ri.item_id = il.item_id 
                      JOIN tbl_employee e ON bol.employee_id = e.employee_id 
                      ORDER BY bol.back_order_date DESC";
$back_orders_result = $conn->query($back_orders_query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Back Orders Management</h2>
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
                    <h5 class="mb-0">Back Orders List</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Back Order ID</th>
                                    <th>Order ID</th>
                                    <th>Supplier</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Expected Delivery</th>
                                    <th>Status</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($back_order = $back_orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $back_order['back_order_id']; ?></td>
                                        <td>#<?php echo $back_order['purchase_order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($back_order['supplier_name']); ?></td>
                                        <td><?php echo htmlspecialchars($back_order['item_name']); ?></td>
                                        <td><?php echo $back_order['quantity_back_ordered']; ?></td>
                                        <td><?php echo date('M d, Y', strtotime($back_order['backorder_expected_delivery_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $back_order['status'] == 0 ? 'warning' : 
                                                    ($back_order['status'] == 1 ? 'success' : 'danger'); 
                                            ?>">
                                                <?php 
                                                    echo $back_order['status'] == 0 ? 'Pending' : 
                                                        ($back_order['status'] == 1 ? 'Received' : 'Cancelled'); 
                                                ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($back_order['first_name'] . ' ' . $back_order['last_name']); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewBackOrderModal<?php echo $back_order['back_order_id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <?php if ($back_order['status'] == 0): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-success" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#receiveBackOrderModal<?php echo $back_order['back_order_id']; ?>">
                                                    <i class="bi bi-box-seam"></i> Receive
                                                </button>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelBackOrderModal<?php echo $back_order['back_order_id']; ?>">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- View Back Order Modal -->
                                    <div class="modal fade" id="viewBackOrderModal<?php echo $back_order['back_order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Back Order Details</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p><strong>Back Order ID:</strong> #<?php echo $back_order['back_order_id']; ?></p>
                                                    <p><strong>Order ID:</strong> #<?php echo $back_order['purchase_order_id']; ?></p>
                                                    <p><strong>Supplier:</strong> <?php echo htmlspecialchars($back_order['supplier_name']); ?></p>
                                                    <p><strong>Item:</strong> <?php echo htmlspecialchars($back_order['item_name']); ?></p>
                                                    <p><strong>Quantity:</strong> <?php echo $back_order['quantity_back_ordered']; ?></p>
                                                    <p><strong>Expected Delivery:</strong> <?php echo date('M d, Y', strtotime($back_order['backorder_expected_delivery_date'])); ?></p>
                                                    <p><strong>Status:</strong> 
                                                        <span class="badge bg-<?php 
                                                            echo $back_order['status'] == 0 ? 'warning' : 
                                                                ($back_order['status'] == 1 ? 'success' : 'danger'); 
                                                        ?>">
                                                            <?php 
                                                                echo $back_order['status'] == 0 ? 'Pending' : 
                                                                    ($back_order['status'] == 1 ? 'Received' : 'Cancelled'); 
                                                            ?>
                                                        </span>
                                                    </p>
                                                    <p><strong>Created By:</strong> <?php echo htmlspecialchars($back_order['first_name'] . ' ' . $back_order['last_name']); ?></p>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Receive Back Order Modal -->
                                    <div class="modal fade" id="receiveBackOrderModal<?php echo $back_order['back_order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Receive Back Order #<?php echo $back_order['back_order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <input type="hidden" name="back_order_id" value="<?php echo $back_order['back_order_id']; ?>">
                                                        <div class="mb-3">
                                                            <label class="form-label">Quantity to Receive</label>
                                                            <input type="number" name="quantity_received" class="form-control" 
                                                                   min="1" max="<?php echo $back_order['quantity_back_ordered']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="receive_back_order" class="btn btn-success">Receive</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancel Back Order Modal -->
                                    <div class="modal fade" id="cancelBackOrderModal<?php echo $back_order['back_order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cancel Back Order #<?php echo $back_order['back_order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to cancel this back order?</p>
                                                    <p class="text-danger">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="back_order_id" value="<?php echo $back_order['back_order_id']; ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="cancel_back_order" class="btn btn-danger">Cancel Back Order</button>
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

<?php require_once '../../includes/footer.php'; ?> 