<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Cashier
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Cashier') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get today's sales for this cashier
$query = "SELECT COUNT(*) as total_orders, COALESCE(SUM(order_total_amount), 0) as total_sales 
          FROM tbl_pos_orders 
          WHERE DATE(order_date) = CURDATE() 
          AND order_status = 'completed'
          AND employee_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$today_sales = $stmt->get_result()->fetch_assoc();

// Get pending orders for this cashier
$query = "SELECT COUNT(*) as total 
          FROM tbl_pos_orders 
          WHERE order_status = 'pending'
          AND employee_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$pending_orders = $stmt->get_result()->fetch_assoc()['total'];

// Get total orders for this cashier
$query = "SELECT COUNT(*) as total 
          FROM tbl_pos_orders 
          WHERE employee_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$total_orders = $stmt->get_result()->fetch_assoc()['total'];

// Get recent orders
$query = "SELECT po.*, c.customer_name
          FROM tbl_pos_orders po
          LEFT JOIN tbl_customer c ON po.cust_id = c.cust_id
          WHERE po.employee_id = ?
          ORDER BY po.order_date DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$recent_orders = $stmt->get_result();
?>

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card bg-primary text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Today's Sales</h6>
                        <h3 class="display-6 mb-0">₱<?php echo number_format($today_sales['total_sales'], 2); ?></h3>
                    </div>
                    <i class="bi bi-currency-dollar fs-1"></i>
                </div>
                <small><?php echo $today_sales['total_orders']; ?> orders today</small>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Pending Orders</h6>
                        <h3 class="display-6 mb-0"><?php echo $pending_orders; ?></h3>
                    </div>
                    <i class="bi bi-clock-history fs-1"></i>
                </div>
                <small>Need processing</small>
            </div>
        </div>
    </div>

    <div class="col-md-4 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Total Orders</h6>
                        <h3 class="display-6 mb-0"><?php echo $total_orders; ?></h3>
                    </div>
                    <i class="bi bi-cart-check fs-1"></i>
                </div>
                <small>All time</small>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="pos.php" class="btn btn-primary">
                        <i class="bi bi-cart-plus"></i> New Sale
                    </a>
                    <a href="orders.php" class="btn btn-warning">
                        <i class="bi bi-clock-history"></i> View Pending Orders
                    </a>
                    <a href="sales_history.php" class="btn btn-info">
                        <i class="bi bi-journal-text"></i> Sales History
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Orders</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php
                    if ($recent_orders->num_rows > 0) {
                        while ($order = $recent_orders->fetch_assoc()) {
                            $status_class = '';
                            switch ($order['order_status']) {
                                case 'completed':
                                    $status_class = 'text-success';
                                    break;
                                case 'pending':
                                    $status_class = 'text-warning';
                                    break;
                                case 'cancelled':
                                    $status_class = 'text-danger';
                                    break;
                            }
                            
                            echo '<div class="list-group-item">';
                            echo '<div class="d-flex w-100 justify-content-between">';
                            echo '<h6 class="mb-1">Order #' . $order['pos_order_id'] . '</h6>';
                            echo '<small class="' . $status_class . '">' . ucfirst($order['order_status']) . '</small>';
                            echo '</div>';
                            echo '<p class="mb-1">₱' . number_format($order['order_total_amount'], 2) . '</p>';
                            echo '<small>Customer: ' . htmlspecialchars($order['customer_name']) . '</small><br>';
                            echo '<small class="text-muted">' . date('M d, Y H:i', strtotime($order['order_date'])) . '</small>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center mb-0">No recent orders</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 