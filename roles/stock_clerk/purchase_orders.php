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

// Handle purchase order creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create_order') {
        $supplier_id = $_POST['supplier_id'];
        $expected_delivery_date = $_POST['expected_delivery_date'];
        $items = $_POST['items']; // Array of items with quantities
        
        $conn->begin_transaction();
        
        try {
            // Create purchase order
            $stmt = $conn->prepare("INSERT INTO tbl_purchase_order_list (supplier_id, status, purchase_expected_delivery_date, employee_id) VALUES (?, 'ordered', ?, ?)");
            $stmt->bind_param("isi", $supplier_id, $expected_delivery_date, $_SESSION['employee_id']);
            $stmt->execute();
            $purchase_order_id = $conn->insert_id;
            
            // Add items to purchase order
            $stmt = $conn->prepare("INSERT INTO tbl_purchase_items (purchase_order_id, raw_ingredient_id, quantity_ordered, employee_id) VALUES (?, ?, ?, ?)");
            
            foreach ($items as $item) {
                if (!empty($item['ingredient_id']) && !empty($item['quantity'])) {
                    $stmt->bind_param("iiii", $purchase_order_id, $item['ingredient_id'], $item['quantity'], $_SESSION['employee_id']);
                    $stmt->execute();
                }
            }
            
            // Log transaction
            $description = "Created new purchase order #$purchase_order_id (by " . $_SESSION['full_name'] . ")";
            $log_stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description) VALUES ('Purchase Order Create', ?)");
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            $conn->commit();
            $message = "Purchase order created successfully.";
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    }
}

// Get all purchase orders with their status
$query = "SELECT p.*, s.supplier_name, 
          CONCAT(e.first_name, ' ', e.last_name) as employee_name
          FROM tbl_purchase_order_list p
          LEFT JOIN tbl_suppliers s ON p.supplier_id = s.supplier_id
          LEFT JOIN tbl_employee e ON p.employee_id = e.employee_id
          ORDER BY p.purchase_order_date DESC";
$result = $conn->query($query);

// Get all suppliers for the dropdown
$suppliers_query = "SELECT * FROM tbl_suppliers ORDER BY supplier_name";
$suppliers_result = $conn->query($suppliers_query);

// Get all raw ingredients for the dropdown
$ingredients_query = "SELECT * FROM tbl_raw_ingredients ORDER BY raw_name";
$ingredients_result = $conn->query($ingredients_query);
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
            <h5 class="mb-0">Purchase Orders List</h5>
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
                                        'received' => 'bg-success',
                                        'partially_received' => 'bg-warning',
                                        'back_ordered' => 'bg-danger',
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
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewOrderModal<?php echo $order['purchase_order_id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>

                            <!-- View Order Modal -->
                            <div class="modal fade" id="viewOrderModal<?php echo $order['purchase_order_id']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Purchase Order #<?php echo $order['purchase_order_id']; ?></h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <div class="row mb-3">
                                                <div class="col-md-6">
                                                    <p><strong>Supplier:</strong> <?php echo htmlspecialchars($order['supplier_name']); ?></p>
                                                    <p><strong>Order Date:</strong> <?php echo date('M d, Y', strtotime($order['purchase_order_date'])); ?></p>
                                                    <p><strong>Expected Delivery:</strong> <?php echo date('M d, Y', strtotime($order['purchase_expected_delivery_date'])); ?></p>
                                                </div>
                                                <div class="col-md-6">
                                                    <p><strong>Status:</strong> 
                                                        <span class="badge <?php echo $status_class; ?>">
                                                            <?php echo ucwords(str_replace('_', ' ', $order['status'])); ?>
                                                        </span>
                                                    </p>
                                                    <p><strong>Created By:</strong> <?php echo htmlspecialchars($order['employee_name']); ?></p>
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
                                                        ?>
                                                            <tr>
                                                                <td><?php echo htmlspecialchars($item['raw_name']); ?></td>
                                                                <td><?php echo $item['quantity_ordered'] . ' ' . $item['raw_unit_of_measure']; ?></td>
                                                                <td><?php echo $item['quantity_received'] . ' ' . $item['raw_unit_of_measure']; ?></td>
                                                                <td><?php echo $item['back_ordered_quantity'] . ' ' . $item['raw_unit_of_measure']; ?></td>
                                                            </tr>
                                                        <?php endwhile; ?>
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
                        <?php endwhile; ?>
                    </tbody>
                </table>
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
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="create_order">
                    
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Supplier</label>
                            <select class="form-select" name="supplier_id" required>
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
                            <input type="date" class="form-control" name="expected_delivery_date" required>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label">Order Items</label>
                        <div id="orderItems">
                            <div class="row mb-2">
                                <div class="col-md-5">
                                    <select class="form-select" name="items[0][ingredient_id]" required>
                                        <option value="">Select Ingredient</option>
                                        <?php 
                                        $ingredients_result->data_seek(0);
                                        while ($ingredient = $ingredients_result->fetch_assoc()): 
                                        ?>
                                            <option value="<?php echo $ingredient['raw_ingredient_id']; ?>">
                                                <?php echo htmlspecialchars($ingredient['raw_name']); ?> 
                                                (<?php echo $ingredient['raw_unit_of_measure']; ?>)
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="col-md-5">
                                    <input type="number" class="form-control" name="items[0][quantity]" required min="1" step="0.01">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-danger btn-sm remove-item" style="display: none;">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-secondary btn-sm mt-2" id="addItem">
                            <i class="bi bi-plus-circle"></i> Add Item
                        </button>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Order</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const orderItems = document.getElementById('orderItems');
    const addItemBtn = document.getElementById('addItem');
    let itemCount = 1;

    addItemBtn.addEventListener('click', function() {
        const newRow = document.createElement('div');
        newRow.className = 'row mb-2';
        newRow.innerHTML = `
            <div class="col-md-5">
                <select class="form-select" name="items[${itemCount}][ingredient_id]" required>
                    <option value="">Select Ingredient</option>
                    <?php 
                    $ingredients_result->data_seek(0);
                    while ($ingredient = $ingredients_result->fetch_assoc()): 
                    ?>
                        <option value="<?php echo $ingredient['raw_ingredient_id']; ?>">
                            <?php echo htmlspecialchars($ingredient['raw_name']); ?> 
                            (<?php echo $ingredient['raw_unit_of_measure']; ?>)
                        </option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="col-md-5">
                <input type="number" class="form-control" name="items[${itemCount}][quantity]" required min="1" step="0.01">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger btn-sm remove-item">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        `;
        orderItems.appendChild(newRow);
        itemCount++;
    });

    orderItems.addEventListener('click', function(e) {
        if (e.target.closest('.remove-item')) {
            e.target.closest('.row').remove();
        }
    });
});
</script>

<?php require_once '../../includes/footer.php'; ?> 