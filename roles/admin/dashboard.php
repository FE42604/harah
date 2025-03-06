<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Admin
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Admin') {
    header("Location: ../../auth/login.php");
    exit();
}

// Get total products
$query = "SELECT COUNT(*) as total FROM tbl_products";
$result = $conn->query($query);
$total_products = $result->fetch_assoc()['total'];

// Get low stock products
$query = "SELECT COUNT(*) as total FROM tbl_products WHERE product_quantity <= product_restock_qty";
$result = $conn->query($query);
$low_stock = $result->fetch_assoc()['total'];

// Get today's sales
$query = "SELECT COUNT(*) as total_orders, COALESCE(SUM(order_total_amount), 0) as total_sales 
          FROM tbl_pos_orders 
          WHERE DATE(order_date) = CURDATE() AND order_status = 'completed'";
$result = $conn->query($query);
$today_sales = $result->fetch_assoc();

// Get total employees
$query = "SELECT COUNT(*) as total FROM tbl_employee";
$result = $conn->query($query);
$total_employees = $result->fetch_assoc()['total'];
?>

<div class="row">
    <div class="col-md-3 mb-4">
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

    <div class="col-md-3 mb-4">
        <div class="card bg-success text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Total Products</h6>
                        <h3 class="display-6 mb-0"><?php echo $total_products; ?></h3>
                    </div>
                    <i class="bi bi-box-seam fs-1"></i>
                </div>
                <small>In inventory</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card bg-warning text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Low Stock Items</h6>
                        <h3 class="display-6 mb-0"><?php echo $low_stock; ?></h3>
                    </div>
                    <i class="bi bi-exclamation-triangle fs-1"></i>
                </div>
                <small>Need reordering</small>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card bg-info text-white h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-uppercase mb-1">Total Employees</h6>
                        <h3 class="display-6 mb-0"><?php echo $total_employees; ?></h3>
                    </div>
                    <i class="bi bi-people fs-1"></i>
                </div>
                <small>Active employees</small>
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
                    <a href="employees.php" class="btn btn-success">
                        <i class="bi bi-people"></i> Manage Employees
                    </a>
                    <a href="reports.php" class="btn btn-info">
                        <i class="bi bi-file-earmark-text"></i> View Reports
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Recent Activities</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <?php
                    // Get current user's info for comparison
                    $current_user_id = $_SESSION['user_id'];
                    
                    $query = "SELECT t.* 
                             FROM tbl_transaction_log t
                             ORDER BY t.transaction_date DESC LIMIT 5";
                    $result = $conn->query($query);
                    
                    if ($result && $result->num_rows > 0) {
                        while ($activity = $result->fetch_assoc()) {
                            echo '<div class="list-group-item">';
                            echo '<div class="d-flex w-100 justify-content-between">';
                            echo '<h6 class="mb-1">' . htmlspecialchars(ucwords(str_replace('_', ' ', $activity['transaction_type']))) . '</h6>';
                            echo '<small>' . date('M d, Y H:i', strtotime($activity['transaction_date'])) . '</small>';
                            echo '</div>';
                            
                            // Extract the creator's name from the description if it exists
                            $description = $activity['transaction_description'];
                            $creator_name = '';
                            
                            // Check if description contains creator info
                            if (preg_match('/\(by (.*?)\)$/', $description, $matches)) {
                                $creator_name = $matches[1];
                                $description = trim(str_replace('(by ' . $creator_name . ')', '', $description));
                            }
                            
                            echo '<p class="mb-1">' . htmlspecialchars($description) . '</p>';
                            echo '<div class="d-flex justify-content-between align-items-center">';
                            if ($activity['transaction_amount']) {
                                echo '<small class="text-muted">Amount: ₱' . number_format($activity['transaction_amount'], 2) . '</small>';
                            }
                            
                            // Display the creator's name
                            if ($creator_name) {
                                if ($creator_name === $_SESSION['full_name']) {
                                    echo '<small class="text-primary">By: You</small>';
                                } else {
                                    echo '<small class="text-muted">By: ' . htmlspecialchars($creator_name) . '</small>';
                                }
                            } else {
                                echo '<small class="text-muted">By: localhost</small>';
                            }
                            echo '</div>';
                            echo '</div>';
                        }
                    } else {
                        echo '<p class="text-muted text-center mb-0">No recent activities</p>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 