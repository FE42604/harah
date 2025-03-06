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

// Handle order receipt
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'receive_order') {
        $purchase_order_id = $_POST['purchase_order_id'];
        $items = $_POST['items']; // Array of items with received quantities
        
        $conn->begin_transaction();
        
        try {
            $all_received = true;
            $partially_received = false;
            
            // Update each item's received quantity
            $stmt = $conn->prepare("UPDATE tbl_purchase_items 
                                  SET quantity_received = quantity_received + ?,
                                      back_ordered_quantity = GREATEST(0, quantity_ordered - (quantity_received + ?))
                                  WHERE purchase_order_id = ? AND raw_ingredient_id = ?");
            
            foreach ($items as $item) {
                if (!empty($item['received_quantity'])) {
                    $stmt->bind_param("iiii", 
                        $item['received_quantity'],
                        $item['received_quantity'],
                        $purchase_order_id,
                        $item['ingredient_id']
                    );
                    $stmt->execute();
                    
                    // Update raw ingredient stock
                    $update_stock = $conn->prepare("UPDATE tbl_raw_ingredients 
                                                  SET raw_stock = raw_stock + ? 
                                                  WHERE raw_ingredient_id = ?");
                    $update_stock->bind_param("ii", $item['received_quantity'], $item['ingredient_id']);
                    $update_stock->execute();
                    
                    // Check if item is fully received
                    $check_received = $conn->prepare("SELECT quantity_ordered, quantity_received 
                                                    FROM tbl_purchase_items 
                                                    WHERE purchase_order_id = ? AND raw_ingredient_id = ?");
                    $check_received->bind_param("ii", $purchase_order_id, $item['ingredient_id']);
                    $check_received->execute();
                    $result = $check_received->get_result();
                    $item_status = $result->fetch_assoc();
                    
                    if ($item_status['quantity_received'] < $item_status['quantity_ordered']) {
                        $all_received = false;
                        $partially_received = true;
                    }
                }
            }
            
            // Update purchase order status
            $status = $all_received ? 'received' : ($partially_received ? 'partially_received' : 'back_ordered');
            $update_status = $conn->prepare("UPDATE tbl_purchase_order_list 
                                           SET status = ? 
                                           WHERE purchase_order_id = ?");
            $update_status->bind_param("si", $status, $purchase_order_id);
            $update_status->execute();
            
            // Log transaction
            $description = "Received items for purchase order #$purchase_order_id (by " . $_SESSION['full_name'] . ")";
            $log_stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description) VALUES ('Purchase Order Receive', ?)");
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            $conn->commit();
            $message = "Order items received successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Get all purchase orders that need receiving
$query = "SELECT p.*, s.supplier_name, 
          CONCAT(e.first_name, ' ', e.last_name) as employee_name
          FROM tbl_purchase_order_list p
          LEFT JOIN tbl_suppliers s ON p.supplier_id = s.supplier_id
          LEFT JOIN tbl_employee e ON p.employee_id = e.employee_id
          WHERE p.status IN ('ordered', 'partially_received')
          ORDER BY p.purchase_order_date DESC";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Receive Purchase Orders</h2>
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
            <h5 class="mb-0">Pending Orders</h5>
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
                            <th>Created By</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($order = $result->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $order['purchase_order_id']; ?></td>
                                <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['purchase_order_date'])); ?></td>
                                <td><?php echo date('M d, Y', strtotime($order['purchase_expected_delivery_date'])); ?></td>
                                <td>
                                    <?php
                                    $status_class = match($order['status']) {
                                        'ordered' => 'bg-primary',
                                        'partially_received' => 'bg-warning',
                                        default => 'bg-secondary'
                                    };
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($order['employee_name']); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-success" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#receiveOrderModal<?php echo $order['purchase_order_id']; ?>">
                                        <i class="bi bi-box-seam"></i> Receive
                                    </button>
                                </td>
                            </tr>

                            <!-- Receive Order Modal -->
                            <div class="modal fade" id="receiveOrderModal<?php echo $order['purchase_order_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Receive Order #<?php echo $order['purchase_order_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="action" value="receive_order">
                                                <input type="hidden" name="purchase_order_id" value="<?php echo $order['purchase_order_id']; ?>">
                                                
                                                <div class="row mb-3">
                                                    <div class="col-md-6">
                                                        <p><strong>Supplier:</strong> <?php echo htmlspecialchars($order['supplier_name']); ?></p>
                                                        <p><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($order['purchase_order_date'])); ?></p>
                                                        <p><strong>Expected Delivery:</strong> <?php echo date('M d, Y', strtotime($order['purchase_expected_delivery_date'])); ?></p>
                                                    </div>
                                                </div>
                                                
                                                <h6>Order Items</h6>
                                                <div class="table-responsive">
                                                    <table class="table table-sm">
                                                        <thead>
                                                            <tr>
                                                                <th>Ingredient</th>
                                                                <th>Quantity Ordered</th>
                                                                <th>Quantity Received</th>
                                                                <th>Back Ordered</th>
                                                                <th>Receive Quantity</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            <?php
                                                            $items_query = "SELECT pi.*, ri.raw_name, ri.raw_unit_of_measure 
                                                                          FROM tbl_purchase_items pi
                                                                          JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id
                                                                          WHERE pi.purchase_order_id = ?";
                                                            $items_stmt = $conn->prepare($items_query);
                                                            $items_stmt->bind_param("i", $order['purchase_order_id']);
                                                            $items_stmt->execute();
                                                            $items_result = $items_stmt->get_result();
                                                            
                                                            while ($item = $items_result->fetch_assoc()):
                                                                $remaining = $item['quantity_ordered'] - $item['quantity_received'];
                                                            ?>
                                                                <tr>
                                                                    <td><?php echo htmlspecialchars($item['raw_name']); ?></td>
                                                                    <td><?php echo $item['quantity_ordered'] . ' ' . $item['raw_unit_of_measure']; ?></td>
                                                                    <td><?php echo $item['quantity_received'] . ' ' . $item['raw_unit_of_measure']; ?></td>
                                                                    <td><?php echo $item['back_ordered_quantity'] . ' ' . $item['raw_unit_of_measure']; ?></td>
                                                                    <td>
                                                                        <?php if ($remaining > 0): ?>
                                                                            <input type="hidden" name="items[<?php echo $item['raw_ingredient_id']; ?>][ingredient_id]" 
                                                                                   value="<?php echo $item['raw_ingredient_id']; ?>">
                                                                            <input type="number" 
                                                                                   class="form-control form-control-sm" 
                                                                                   name="items[<?php echo $item['raw_ingredient_id']; ?>][received_quantity]" 
                                                                                   min="0" 
                                                                                   max="<?php echo $remaining; ?>" 
                                                                                   step="0.01"
                                                                                   required>
                                                                            <small class="text-muted">Max: <?php echo $remaining; ?></small>
                                                                        <?php else: ?>
                                                                            <span class="badge bg-success">Fully Received</span>
                                                                        <?php endif; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endwhile; ?>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" class="btn btn-success">Receive Items</button>
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

<?php require_once '../../includes/footer.php'; ?> 