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

// Handle supplier creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add_supplier') {
        $supplier_name = $_POST['supplier_name'];
        $contact_person = $_POST['contact_person'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $email = $_POST['email'];
        
        // Get employee name for logging
        $employee_query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
        $emp_stmt = $conn->prepare($employee_query);
        $emp_stmt->bind_param("i", $_SESSION['employee_id']);
        $emp_stmt->execute();
        $emp_result = $emp_stmt->get_result();
        $employee = $emp_result->fetch_assoc();
        $employee_name = $employee['full_name'];
        
        // Insert into tbl_suppliers
        $query = "INSERT INTO tbl_suppliers (supplier_name, contact_person, address, phone, email) 
                  VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("sssss", $supplier_name, $contact_person, $address, $phone, $email);
        
        if ($stmt->execute()) {
            // Log the transaction
            $description = "Added new supplier: $supplier_name (by $employee_name)";
            $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                        VALUES ('supplier_create', ?, NOW())";
            $log_stmt = $conn->prepare($log_query);
            $log_stmt->bind_param("s", $description);
            $log_stmt->execute();
            
            $message = "Supplier added successfully.";
        } else {
            $error = "Error adding supplier: " . $conn->error;
        }
    }
}

// Get all suppliers
$query = "SELECT * FROM tbl_suppliers ORDER BY created_at DESC";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Suppliers</h2>
        </div>
        <div class="col text-end">
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSupplierModal">
                <i class="bi bi-plus-circle"></i> Add New Supplier
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
            <h5 class="mb-0">Suppliers List</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Supplier Name</th>
                            <th>Contact Person</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($supplier = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($supplier['supplier_name']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['contact_person']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['address']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['phone']); ?></td>
                                <td><?php echo htmlspecialchars($supplier['email']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($supplier['created_at'])); ?></td>
                                <td>
                                    <button type="button" 
                                            class="btn btn-sm btn-info" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#viewSupplierModal<?php echo $supplier['supplier_id']; ?>">
                                        <i class="bi bi-eye"></i> View
                                    </button>
                                </td>
                            </tr>

                            <!-- View Supplier Modal -->
                            <div class="modal fade" id="viewSupplierModal<?php echo $supplier['supplier_id']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Supplier Details</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p><strong>Name:</strong> <?php echo htmlspecialchars($supplier['supplier_name']); ?></p>
                                            <p><strong>Contact Person:</strong> <?php echo htmlspecialchars($supplier['contact_person']); ?></p>
                                            <p><strong>Address:</strong> <?php echo htmlspecialchars($supplier['address']); ?></p>
                                            <p><strong>Phone:</strong> <?php echo htmlspecialchars($supplier['phone']); ?></p>
                                            <p><strong>Email:</strong> <?php echo htmlspecialchars($supplier['email']); ?></p>
                                            <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($supplier['created_at'])); ?></p>
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

<!-- Add Supplier Modal -->
<div class="modal fade" id="addSupplierModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Supplier</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add_supplier">
                    
                    <div class="mb-3">
                        <label class="form-label">Supplier Name</label>
                        <input type="text" class="form-control" name="supplier_name" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Contact Person</label>
                        <input type="text" class="form-control" name="contact_person" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea class="form-control" name="address" rows="3" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Phone</label>
                        <input type="tel" class="form-control" name="phone" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Email</label>
                        <input type="email" class="form-control" name="email" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Add Supplier</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 