<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Stock Clerk
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Stock Clerk') {
    header("Location: ../../auth/login.php");
    exit();
}

$employee_id = $_SESSION['employee_id'];

// Helper Functions
function getEmployeeName($conn, $employee_id) {
    $query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    return $employee['full_name'];
}

function logTransaction($conn, $type, $description) {
    $query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
              VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $type, $description);
    return $stmt->execute();
}

function updateInventoryQuantity($conn, $raw_ingredient_id, $quantity_change, $type) {
    $query = "UPDATE tbl_raw_ingredients 
              SET current_stock = current_stock " . ($type === 'add' ? '+' : '-') . " ? 
              WHERE raw_ingredient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $quantity_change, $raw_ingredient_id);
    $stmt->execute();
}

function getPurchaseOrderItems($conn, $order_id) {
    $query = "SELECT pi.*, il.name, ri.raw_ingredient_id 
              FROM tbl_purchase_items pi 
              JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id 
              JOIN tbl_item_list il ON ri.item_id = il.item_id 
              WHERE pi.purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

function getBackOrderDetails($conn, $back_order_id) {
    $query = "SELECT bol.*, pi.purchase_order_id, pi.purchase_item_id 
              FROM tbl_back_order_list bol 
              JOIN tbl_purchase_items pi ON bol.purchase_item_id = pi.purchase_item_id 
              WHERE bol.back_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $back_order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function getReturnDetails($conn, $return_id) {
    $query = "SELECT rl.*, pi.purchase_order_id, pi.purchase_item_id 
              FROM tbl_return_list rl 
              JOIN tbl_purchase_items pi ON rl.purchase_item_id = pi.purchase_item_id 
              WHERE rl.return_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $return_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

function updatePurchaseOrderStatus($conn, $order_id, $status) {
    $query = "UPDATE tbl_purchase_order_list SET status = ? WHERE purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $order_id);
    return $stmt->execute();
}

function checkPurchaseOrderCompletion($conn, $order_id) {
    $query = "SELECT COUNT(*) as total, 
                     SUM(CASE WHEN quantity_received >= quantity_ordered THEN 1 ELSE 0 END) as received,
                     SUM(CASE WHEN back_ordered_quantity > 0 THEN 1 ELSE 0 END) as back_ordered
              FROM tbl_purchase_items 
              WHERE purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    
    if ($counts['total'] == $counts['received']) {
        return 'received';
    } elseif ($counts['back_ordered'] > 0) {
        return 'back_ordered';
    } elseif ($counts['received'] > 0) {
        return 'partially_received';
    } else {
        return 'ordered';
    }
}

function validateReturnQuantity($conn, $purchase_item_id, $quantity_returned) {
    $query = "SELECT quantity_received FROM tbl_purchase_items WHERE purchase_item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    return $quantity_returned <= $item['quantity_received'];
}

function validateBackOrderQuantity($conn, $purchase_item_id, $quantity_back_ordered) {
    $query = "SELECT quantity_ordered, quantity_received, back_ordered_quantity 
              FROM tbl_purchase_items 
              WHERE purchase_item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $remaining = $item['quantity_ordered'] - $item['quantity_received'] - $item['back_ordered_quantity'];
    return $quantity_back_ordered <= $remaining;
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['receive_order'])) {
        $purchase_order_id = $_POST['purchase_order_id'];
        $items = $_POST['items'];
        
        foreach ($items as $purchase_item_id => $quantity_received) {
            // Insert into receiving_list
            $stmt = $conn->prepare("INSERT INTO tbl_receiving_list (purchase_item_id, quantity_received, employee_id) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $purchase_item_id, $quantity_received, $employee_id);
            $stmt->execute();

            // Update purchase_items quantity_received
            $stmt = $conn->prepare("UPDATE tbl_purchase_items SET quantity_received = quantity_received + ? WHERE purchase_item_id = ?");
            $stmt->bind_param("ii", $quantity_received, $purchase_item_id);
            $stmt->execute();

            // Get raw_ingredient_id for inventory update
            $stmt = $conn->prepare("SELECT raw_ingredient_id FROM tbl_purchase_items WHERE purchase_item_id = ?");
            $stmt->bind_param("i", $purchase_item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();

            // Update inventory
            updateInventoryQuantity($conn, $item['raw_ingredient_id'], $quantity_received, 'add');
        }

        // Check order status and update if needed
        $status = checkPurchaseOrderCompletion($conn, $purchase_order_id);
        updatePurchaseOrderStatus($conn, $purchase_order_id, $status);

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Received items for purchase order #" . $purchase_order_id . " (by " . $employee_name . ")";
        logTransaction($conn, 'receive', $description);

        $success = "Items received successfully.";
    }

    if (isset($_POST['create_back_order'])) {
        $purchase_item_id = $_POST['purchase_item_id'];
        $quantity_back_ordered = $_POST['quantity_back_ordered'];
        $expected_delivery_date = $_POST['expected_delivery_date'];
        
        // Validate back order quantity
        if (!validateBackOrderQuantity($conn, $purchase_item_id, $quantity_back_ordered)) {
            $error = "Invalid back order quantity. Please check the remaining quantity.";
        } else {
            // Insert into back_order_list
            $stmt = $conn->prepare("INSERT INTO tbl_back_order_list (purchase_item_id, quantity_back_ordered, backorder_expected_delivery_date, employee_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iisi", $purchase_item_id, $quantity_back_ordered, $expected_delivery_date, $employee_id);
            $stmt->execute();

            // Update purchase_items back_ordered_quantity
            $stmt = $conn->prepare("UPDATE tbl_purchase_items SET back_ordered_quantity = back_ordered_quantity + ? WHERE purchase_item_id = ?");
            $stmt->bind_param("ii", $quantity_back_ordered, $purchase_item_id);
            $stmt->execute();

            // Get purchase order details for status update
            $stmt = $conn->prepare("SELECT purchase_order_id FROM tbl_purchase_items WHERE purchase_item_id = ?");
            $stmt->bind_param("i", $purchase_item_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $purchase_item = $result->fetch_assoc();
            $purchase_order_id = $purchase_item['purchase_order_id'];

            // Update purchase order status
            updatePurchaseOrderStatus($conn, $purchase_order_id, 'back_ordered');

            // Log the transaction
            $employee_name = getEmployeeName($conn, $employee_id);
            $description = "Created back order for purchase order #" . $purchase_order_id . " (by " . $employee_name . ")";
            logTransaction($conn, 'back_order', $description);

            $success = "Back order created successfully.";
        }
    }

    if (isset($_POST['receive_back_order'])) {
        $back_order_id = $_POST['back_order_id'];
        $quantity_received = $_POST['quantity_received'];
        
        // Get back order details
        $back_order = getBackOrderDetails($conn, $back_order_id);
        
        // Validate quantity
        if ($quantity_received > $back_order['quantity_back_ordered']) {
            $error = "Invalid quantity. Cannot receive more than back ordered amount.";
        } else {
            // Insert into receiving_list
            $stmt = $conn->prepare("INSERT INTO tbl_receiving_list (purchase_item_id, quantity_received, employee_id) VALUES (?, ?, ?)");
            $stmt->bind_param("iii", $back_order['purchase_item_id'], $quantity_received, $employee_id);
            $stmt->execute();

            // Update purchase_items quantities
            $stmt = $conn->prepare("UPDATE tbl_purchase_items SET 
                                   back_ordered_quantity = back_ordered_quantity - ?,
                                   quantity_received = quantity_received + ?
                                   WHERE purchase_item_id = ?");
            $stmt->bind_param("iii", $quantity_received, $quantity_received, $back_order['purchase_item_id']);
            $stmt->execute();

            // Get raw_ingredient_id for inventory update
            $stmt = $conn->prepare("SELECT raw_ingredient_id FROM tbl_purchase_items WHERE purchase_item_id = ?");
            $stmt->bind_param("i", $back_order['purchase_item_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            $item = $result->fetch_assoc();

            // Update inventory
            updateInventoryQuantity($conn, $item['raw_ingredient_id'], $quantity_received, 'add');

            // Check and update purchase order status
            $status = checkPurchaseOrderCompletion($conn, $back_order['purchase_order_id']);
            updatePurchaseOrderStatus($conn, $back_order['purchase_order_id'], $status);

            // Log the transaction
            $employee_name = getEmployeeName($conn, $employee_id);
            $description = "Received back order #" . $back_order_id . " for purchase order #" . $back_order['purchase_order_id'] . " (by " . $employee_name . ")";
            logTransaction($conn, 'back_order_receive', $description);

            $success = "Back order received successfully.";
        }
    }

    if (isset($_POST['cancel_back_order'])) {
        $back_order_id = $_POST['back_order_id'];
        
        // Get back order details
        $back_order = getBackOrderDetails($conn, $back_order_id);
        
        // Update purchase_items back_ordered_quantity
        $stmt = $conn->prepare("UPDATE tbl_purchase_items SET back_ordered_quantity = back_ordered_quantity - ? WHERE purchase_item_id = ?");
        $stmt->bind_param("ii", $back_order['quantity_back_ordered'], $back_order['purchase_item_id']);
        $stmt->execute();

        // Check and update purchase order status
        $status = checkPurchaseOrderCompletion($conn, $back_order['purchase_order_id']);
        updatePurchaseOrderStatus($conn, $back_order['purchase_order_id'], $status);

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Cancelled back order #" . $back_order_id . " for purchase order #" . $back_order['purchase_order_id'] . " (by " . $employee_name . ")";
        logTransaction($conn, 'back_order_cancel', $description);

        $success = "Back order cancelled successfully.";
    }

    if (isset($_POST['create_return'])) {
        $purchase_item_id = $_POST['purchase_item_id'];
        $quantity_returned = $_POST['quantity_returned'];
        $return_reason = $_POST['return_reason'];
        
        // Validate return quantity
        if (!validateReturnQuantity($conn, $purchase_item_id, $quantity_returned)) {
            $error = "Invalid return quantity. Cannot return more than received amount.";
        } else {
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
    }

    if (isset($_POST['complete_return'])) {
        $return_id = $_POST['return_id'];
        
        // Get return details
        $return = getReturnDetails($conn, $return_id);
        
        // Update return status
        $stmt = $conn->prepare("UPDATE tbl_return_list SET status = 1 WHERE return_id = ?");
        $stmt->bind_param("i", $return_id);
        $stmt->execute();

        // Update purchase_items quantity_received
        $stmt = $conn->prepare("UPDATE tbl_purchase_items SET quantity_received = quantity_received - ? WHERE purchase_item_id = ?");
        $stmt->bind_param("ii", $return['quantity_returned'], $return['purchase_item_id']);
        $stmt->execute();

        // Get raw_ingredient_id for inventory update
        $stmt = $conn->prepare("SELECT raw_ingredient_id FROM tbl_purchase_items WHERE purchase_item_id = ?");
        $stmt->bind_param("i", $return['purchase_item_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        $item = $result->fetch_assoc();

        // Update inventory
        updateInventoryQuantity($conn, $item['raw_ingredient_id'], $return['quantity_returned'], 'subtract');

        // Check and update purchase order status
        $status = checkPurchaseOrderCompletion($conn, $return['purchase_order_id']);
        updatePurchaseOrderStatus($conn, $return['purchase_order_id'], $status);

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
        
        // Update return status
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

// Fetch pending purchase orders
$pending_orders_query = "SELECT pol.*, s.supplier_name, e.first_name, e.last_name 
                        FROM tbl_purchase_order_list pol 
                        JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
                        JOIN tbl_employee e ON pol.employee_id = e.employee_id 
                        WHERE pol.status IN ('ordered', 'partially_received', 'back_ordered')
                        ORDER BY pol.purchase_order_date DESC";
$pending_orders_result = $conn->query($pending_orders_query);

// Fetch back orders
$back_orders_query = "SELECT bol.*, pol.purchase_order_id, s.supplier_name, il.name as item_name, e.first_name, e.last_name 
                      FROM tbl_back_order_list bol 
                      JOIN tbl_purchase_items pi ON bol.purchase_item_id = pi.purchase_item_id 
                      JOIN tbl_purchase_order_list pol ON pi.purchase_order_id = pol.purchase_order_id 
                      JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
                      JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id 
                      JOIN tbl_item_list il ON ri.item_id = il.item_id 
                      JOIN tbl_employee e ON bol.employee_id = e.employee_id 
                      ORDER BY bol.backorder_created_at DESC";
$back_orders_result = $conn->query($back_orders_query);

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
            <h2 class="mb-4">Receive Purchase Orders</h2>
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

    <!-- Pending Orders Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Purchase Orders</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Supplier</th>
                                    <th>Date</th>
                                    <th>Expected Delivery</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $pending_orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['purchase_order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['purchase_order_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['purchase_expected_delivery_date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] === 'ordered' ? 'warning' : 
                                                    ($order['status'] === 'back_ordered' ? 'danger' : 'info'); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-primary" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#receiveOrderModal<?php echo $order['purchase_order_id']; ?>">
                                                <i class="bi bi-box-seam"></i> Receive
                                            </button>
                                            <button type="button" 
                                                    class="btn btn-sm btn-warning" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#backOrderModal<?php echo $order['purchase_order_id']; ?>">
                                                <i class="bi bi-arrow-counterclockwise"></i> Back Order
                                            </button>
                                        </td>
                                    </tr>

                                    <!-- Receive Order Modal -->
                                    <div class="modal fade" id="receiveOrderModal<?php echo $order['purchase_order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Receive Items for Order #<?php echo $order['purchase_order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Item</th>
                                                                        <th>Quantity Ordered</th>
                                                                        <th>Quantity Received</th>
                                                                        <th>Quantity to Receive</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    $items = getPurchaseOrderItems($conn, $order['purchase_order_id']);
                                                                    foreach ($items as $item):
                                                                        $remaining = $item['quantity_ordered'] - $item['quantity_received'];
                                                                        if ($remaining > 0):
                                                                    ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                                            <td><?php echo $item['quantity_ordered']; ?></td>
                                                                            <td><?php echo $item['quantity_received']; ?></td>
                                                                            <td>
                                                                                <input type="hidden" name="purchase_item_id" value="<?php echo $item['purchase_item_id']; ?>">
                                                                                <input type="number" name="quantity_received" class="form-control form-control-sm" 
                                                                                       min="1" max="<?php echo $remaining; ?>" required>
                                                                            </td>
                                                                        </tr>
                                                                    <?php 
                                                                        endif;
                                                                    endforeach; 
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="receive_order" class="btn btn-primary">Receive Items</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Back Order Modal -->
                                    <div class="modal fade" id="backOrderModal<?php echo $order['purchase_order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Create Back Order for Order #<?php echo $order['purchase_order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <form method="POST" action="">
                                                    <div class="modal-body">
                                                        <div class="table-responsive">
                                                            <table class="table table-sm">
                                                                <thead>
                                                                    <tr>
                                                                        <th>Item</th>
                                                                        <th>Quantity Ordered</th>
                                                                        <th>Quantity Received</th>
                                                                        <th>Back Ordered</th>
                                                                        <th>Quantity to Back Order</th>
                                                                    </tr>
                                                                </thead>
                                                                <tbody>
                                                                    <?php
                                                                    foreach ($items as $item):
                                                                        $remaining = $item['quantity_ordered'] - $item['quantity_received'] - $item['back_ordered_quantity'];
                                                                        if ($remaining > 0):
                                                                    ?>
                                                                        <tr>
                                                                            <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                                            <td><?php echo $item['quantity_ordered']; ?></td>
                                                                            <td><?php echo $item['quantity_received']; ?></td>
                                                                            <td><?php echo $item['back_ordered_quantity']; ?></td>
                                                                            <td>
                                                                                <input type="hidden" name="purchase_item_id" value="<?php echo $item['purchase_item_id']; ?>">
                                                                                <input type="number" name="quantity_back_ordered" class="form-control form-control-sm" 
                                                                                       min="1" max="<?php echo $remaining; ?>" required>
                                                                            </td>
                                                                        </tr>
                                                                    <?php 
                                                                        endif;
                                                                    endforeach; 
                                                                    ?>
                                                                </tbody>
                                                            </table>
                                                        </div>
                                                        <div class="mt-3">
                                                            <label class="form-label">Expected Delivery Date for Back Order</label>
                                                            <input type="date" name="expected_delivery_date" class="form-control" required>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <button type="submit" name="create_back_order" class="btn btn-warning">Create Back Order</button>
                                                    </div>
                                                </form>
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

    <!-- Back Orders Section -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Pending Back Orders</h5>
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
                                        <td><?php echo htmlspecialchars($back_order['first_name'] . ' ' . $back_order['last_name']); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewBackOrderModal<?php echo $back_order['back_order_id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
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
                                                        <span class="badge bg-warning">Pending</span>
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

    <!-- Returns Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Returns Management</h5>
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