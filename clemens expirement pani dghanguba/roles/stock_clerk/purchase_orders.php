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

function getItemDetails($conn, $raw_ingredient_id) {
    $query = "SELECT ri.*, il.name, il.unit_of_measure, il.cost as unit_price 
              FROM tbl_raw_ingredients ri 
              JOIN tbl_item_list il ON ri.item_id = il.item_id 
              WHERE ri.raw_ingredient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $raw_ingredient_id);
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

function updatePurchaseOrderTotal($conn, $order_id) {
    $query = "UPDATE tbl_purchase_order_list pol 
              SET total_amount = (
                  SELECT COALESCE(SUM(pi.quantity_ordered * pi.unit_price), 0)
                  FROM tbl_purchase_items pi
                  WHERE pi.purchase_order_id = ?
              )
              WHERE pol.purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $order_id);
    $stmt->execute();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_order'])) {
        $supplier_id = $_POST['supplier_id'];
        $expected_delivery_date = $_POST['expected_delivery_date'];
        $items = $_POST['items'];
        
        // Insert into purchase_order_list
        $stmt = $conn->prepare("INSERT INTO tbl_purchase_order_list (supplier_id, purchase_expected_delivery_date, employee_id) VALUES (?, ?, ?)");
        $stmt->bind_param("isi", $supplier_id, $expected_delivery_date, $employee_id);
        $stmt->execute();
        $purchase_order_id = $conn->insert_id;

        // Insert purchase items
        foreach ($items as $item) {
            $raw_ingredient_id = $item['raw_ingredient_id'];
            $quantity_ordered = $item['quantity_ordered'];
            $unit_price = $item['unit_price'];

            $stmt = $conn->prepare("INSERT INTO tbl_purchase_items (purchase_order_id, raw_ingredient_id, quantity_ordered, unit_price) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("iiid", $purchase_order_id, $raw_ingredient_id, $quantity_ordered, $unit_price);
            $stmt->execute();
        }

        // Update total amount
        updatePurchaseOrderTotal($conn, $purchase_order_id);

        // Log the transaction
        $employee_name = getEmployeeName($conn, $employee_id);
        $description = "Created purchase order #" . $purchase_order_id . " (by " . $employee_name . ")";
        logTransaction($conn, 'purchase_order', $description);

        $success = "Purchase order created successfully.";
    }

    if (isset($_POST['cancel_order'])) {
        $purchase_order_id = $_POST['purchase_order_id'];
        
        // Check if order can be cancelled
        $stmt = $conn->prepare("SELECT COUNT(*) as received FROM tbl_purchase_items WHERE purchase_order_id = ? AND quantity_received > 0");
        $stmt->bind_param("i", $purchase_order_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $count = $result->fetch_assoc();

        if ($count['received'] > 0) {
            $error = "Cannot cancel order. Some items have already been received.";
        } else {
            // Update order status
            updatePurchaseOrderStatus($conn, $purchase_order_id, 'cancelled');

            // Log the transaction
            $employee_name = getEmployeeName($conn, $employee_id);
            $description = "Cancelled purchase order #" . $purchase_order_id . " (by " . $employee_name . ")";
            logTransaction($conn, 'purchase_order_cancel', $description);

            $success = "Purchase order cancelled successfully.";
        }
    }
}

// Fetch suppliers
$suppliers_query = "SELECT * FROM tbl_suppliers ORDER BY supplier_name";
$suppliers_result = $conn->query($suppliers_query);

// Fetch raw ingredients
$ingredients_query = "SELECT ri.*, il.name, il.unit_of_measure, il.cost as unit_price 
                     FROM tbl_raw_ingredients ri 
                     JOIN tbl_item_list il ON ri.item_id = il.item_id 
                     WHERE il.status = 'Active'
                     ORDER BY il.name";
$ingredients_result = $conn->query($ingredients_query);

// Fetch purchase orders
$orders_query = "SELECT pol.*, s.supplier_name, e.first_name, e.last_name,
                        COUNT(pi.purchase_item_id) as item_count,
                        SUM(pi.quantity_ordered) as total_quantity,
                        SUM(pi.quantity_received) as received_quantity,
                        SUM(pi.back_ordered_quantity) as back_ordered_quantity
                 FROM tbl_purchase_order_list pol 
                 JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
                 JOIN tbl_employee e ON pol.employee_id = e.employee_id 
                 LEFT JOIN tbl_purchase_items pi ON pol.purchase_order_id = pi.purchase_order_id
                 GROUP BY pol.purchase_order_id
                 ORDER BY pol.purchase_order_date DESC";
$orders_result = $conn->query($orders_query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Purchase Orders</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createOrderModal">
                <i class="bi bi-plus-circle"></i> Create New Order
            </button>
        </div>
    </div>

    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Supplier</th>
                                    <th>Date</th>
                                    <th>Expected Delivery</th>
                                    <th>Items</th>
                                    <th>Total Quantity</th>
                                    <th>Received</th>
                                    <th>Back Ordered</th>
                                    <th>Status</th>
                                    <th>Total Amount</th>
                                    <th>Created By</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($order = $orders_result->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $order['purchase_order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['supplier_name']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['purchase_order_date'])); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($order['purchase_expected_delivery_date'])); ?></td>
                                        <td><?php echo $order['item_count']; ?></td>
                                        <td><?php echo $order['total_quantity']; ?></td>
                                        <td><?php echo $order['received_quantity']; ?></td>
                                        <td><?php echo $order['back_ordered_quantity']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $order['status'] === 'ordered' ? 'warning' : 
                                                    ($order['status'] === 'back_ordered' ? 'danger' : 
                                                    ($order['status'] === 'received' ? 'success' : 'secondary')); 
                                            ?>">
                                                <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                            </span>
                                        </td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></td>
                                        <td>
                                            <button type="button" 
                                                    class="btn btn-sm btn-info" 
                                                    data-bs-toggle="modal" 
                                                    data-bs-target="#viewOrderModal<?php echo $order['purchase_order_id']; ?>">
                                                <i class="bi bi-eye"></i> View
                                            </button>
                                            <?php if ($order['status'] === 'ordered'): ?>
                                                <button type="button" 
                                                        class="btn btn-sm btn-danger" 
                                                        data-bs-toggle="modal" 
                                                        data-bs-target="#cancelOrderModal<?php echo $order['purchase_order_id']; ?>">
                                                    <i class="bi bi-x-circle"></i> Cancel
                                                </button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>

                                    <!-- View Order Modal -->
                                    <div class="modal fade" id="viewOrderModal<?php echo $order['purchase_order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog modal-lg">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Purchase Order Details #<?php echo $order['purchase_order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <div class="row mb-3">
                                                        <div class="col-md-6">
                                                            <p><strong>Supplier:</strong> <?php echo htmlspecialchars($order['supplier_name']); ?></p>
                                                            <p><strong>Date:</strong> <?php echo date('M d, Y', strtotime($order['purchase_order_date'])); ?></p>
                                                            <p><strong>Expected Delivery:</strong> <?php echo date('M d, Y', strtotime($order['purchase_expected_delivery_date'])); ?></p>
                                                            <p><strong>Status:</strong> 
                                                                <span class="badge bg-<?php 
                                                                    echo $order['status'] === 'ordered' ? 'warning' : 
                                                                        ($order['status'] === 'back_ordered' ? 'danger' : 
                                                                        ($order['status'] === 'received' ? 'success' : 'secondary')); 
                                                                ?>">
                                                                    <?php echo ucfirst(str_replace('_', ' ', $order['status'])); ?>
                                                                </span>
                                                            </p>
                                                        </div>
                                                        <div class="col-md-6">
                                                            <p><strong>Total Amount:</strong> ₱<?php echo number_format($order['total_amount'], 2); ?></p>
                                                            <p><strong>Created By:</strong> <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                                        </div>
                                                    </div>
                                                    <div class="table-responsive">
                                                        <table class="table table-sm">
                                                            <thead>
                                                                <tr>
                                                                    <th>Item</th>
                                                                    <th>Quantity Ordered</th>
                                                                    <th>Quantity Received</th>
                                                                    <th>Back Ordered</th>
                                                                    <th>Unit Price</th>
                                                                    <th>Total</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                <?php
                                                                $items = getPurchaseOrderItems($conn, $order['purchase_order_id']);
                                                                foreach ($items as $item):
                                                                    $item_details = getItemDetails($conn, $item['raw_ingredient_id']);
                                                                ?>
                                                                    <tr>
                                                                        <td><?php echo htmlspecialchars($item_details['name']); ?> 
                                                                            (<?php echo htmlspecialchars($item_details['unit_of_measure']); ?>)</td>
                                                                        <td><?php echo $item['quantity_ordered']; ?></td>
                                                                        <td><?php echo $item['quantity_received']; ?></td>
                                                                        <td><?php echo $item['back_ordered_quantity']; ?></td>
                                                                        <td>₱<?php echo number_format($item['unit_price'], 2); ?></td>
                                                                        <td>₱<?php echo number_format($item['quantity_ordered'] * $item['unit_price'], 2); ?></td>
                                                                    </tr>
                                                                <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Cancel Order Modal -->
                                    <div class="modal fade" id="cancelOrderModal<?php echo $order['purchase_order_id']; ?>" tabindex="-1">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title">Cancel Purchase Order #<?php echo $order['purchase_order_id']; ?></h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <p>Are you sure you want to cancel this purchase order?</p>
                                                    <p class="text-danger">This action cannot be undone.</p>
                                                </div>
                                                <div class="modal-footer">
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="purchase_order_id" value="<?php echo $order['purchase_order_id']; ?>">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                        <button type="submit" name="cancel_order" class="btn btn-danger">Cancel Order</button>
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

<!-- Create Order Modal -->
<div class="modal fade" id="createOrderModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Create New Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="">
                <div class="modal-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select name="supplier_id" class="form-select" required>
                                <option value="">Select Supplier</option>
                                <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>">
                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Expected Delivery Date</label>
                            <input type="date" name="expected_delivery_date" class="form-control" required>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm" id="itemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Unit Price</th>
                                    <th>Total</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <select name="items[0][raw_ingredient_id]" class="form-select item-select" required>
                                            <option value="">Select Item</option>
                                            <?php 
                                            $ingredients_result->data_seek(0);
                                            while ($ingredient = $ingredients_result->fetch_assoc()): 
                                            ?>
                                                <option value="<?php echo $ingredient['raw_ingredient_id']; ?>" 
                                                        data-price="<?php echo $ingredient['unit_price'] ?? 0; ?>">
                                                    <?php echo htmlspecialchars($ingredient['name']); ?> 
                                                    (<?php echo htmlspecialchars($ingredient['unit_of_measure']); ?>)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][quantity_ordered]" class="form-control quantity-input" min="1" required>
                                    </td>
                                    <td>
                                        <input type="number" name="items[0][unit_price]" class="form-control price-input" step="0.01" required>
                                    </td>
                                    <td>
                                        <span class="total-amount">₱0.00</span>
                                    </td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-danger remove-item">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <button type="button" class="btn btn-sm btn-primary" id="addItem">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="create_order" class="btn btn-primary">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const itemsTable = document.getElementById('itemsTable');
    const addItemBtn = document.getElementById('addItem');
    let itemCount = 1;

    // Add new item row
    addItemBtn.addEventListener('click', function() {
        const tbody = itemsTable.querySelector('tbody');
        const newRow = document.createElement('tr');
        newRow.innerHTML = `
            <td>
                <select name="items[${itemCount}][raw_ingredient_id]" class="form-select item-select" required>
                    <option value="">Select Item</option>
                    <?php 
                    $ingredients_result->data_seek(0);
                    while ($ingredient = $ingredients_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $ingredient['raw_ingredient_id']; ?>" 
                                data-price="<?php echo $ingredient['unit_price'] ?? 0; ?>">
                            <?php echo htmlspecialchars($ingredient['name']); ?> 
                            (<?php echo htmlspecialchars($ingredient['unit_of_measure']); ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </td>
            <td>
                <input type="number" name="items[${itemCount}][quantity_ordered]" class="form-control quantity-input" min="1" required>
            </td>
            <td>
                <input type="number" name="items[${itemCount}][unit_price]" class="form-control price-input" step="0.01" required>
            </td>
            <td>
                <span class="total-amount">₱0.00</span>
            </td>
            <td>
                <button type="button" class="btn btn-sm btn-danger remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(newRow);
        itemCount++;
    });

    // Remove item row
    itemsTable.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('tr').remove();
        }
    });

    // Calculate total when quantity or price changes
    itemsTable.addEventListener('input', function(e) {
        if (e.target.classList.contains('quantity-input') || e.target.classList.contains('price-input')) {
            const row = e.target.closest('tr');
            const quantity = parseFloat(row.querySelector('.quantity-input').value) || 0;
            const price = parseFloat(row.querySelector('.price-input').value) || 0;
            const total = quantity * price;
            row.querySelector('.total-amount').textContent = `₱${total.toFixed(2)}`;
        }
    });

    // Set unit price when item is selected
    itemsTable.addEventListener('change', function(e) {
        if (e.target.classList.contains('item-select')) {
            const row = e.target.closest('tr');
            const selectedOption = e.target.options[e.target.selectedIndex];
            const price = selectedOption.dataset.price || 0;
            row.querySelector('.price-input').value = price;
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?> 