<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Admin
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$message = '';
$error = '';

// Handle employee status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $employee_id = $_POST['employee_id'];
    
    // Verify the employee is not an admin or main admin before updating
    $stmt = $conn->prepare("SELECT e.first_name, e.last_name, e.job_id, j.job_name 
                           FROM tbl_employee e 
                           LEFT JOIN tbl_jobs j ON e.job_id = j.job_id 
                           WHERE e.employee_id = ? AND (j.job_name NOT IN ('Main Admin', 'Admin') OR j.job_name IS NULL)");
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        $conn->begin_transaction();
        
        try {
            // Toggle status by setting/unsetting job_id
            $new_job_id = $employee['job_id'] === null ? $employee['previous_job_id'] : null;
            $status_text = $employee['job_id'] === null ? 'activated' : 'deactivated';
            
            if ($employee['job_id'] !== null) {
                // Store the current job_id before deactivating
                $stmt = $conn->prepare("UPDATE tbl_employee SET previous_job_id = job_id, job_id = NULL WHERE employee_id = ?");
            } else {
                // Restore the previous job_id
                $stmt = $conn->prepare("UPDATE tbl_employee SET job_id = previous_job_id WHERE employee_id = ?");
            }
            
            $stmt->bind_param("i", $employee_id);
            
            if ($stmt->execute()) {
                // Log the action
                $description = "Employee {$employee['first_name']} {$employee['last_name']} was {$status_text}";
                $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) VALUES ('update_employee', ?, NOW())");
                $stmt->bind_param("s", $description);
                $stmt->execute();
                
                $conn->commit();
                $message = "Employee status updated successfully.";
            } else {
                throw new Exception("Error updating employee status");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    } else {
        $error = "Unauthorized action.";
    }
}

// Get all non-admin employees with their job roles
$query = "SELECT e.*, j.job_name 
          FROM tbl_employee e 
          LEFT JOIN tbl_jobs j ON e.job_id = j.job_id 
          WHERE j.job_name NOT IN ('Main Admin', 'Admin') OR j.job_name IS NULL
          ORDER BY e.job_id, e.last_name, e.first_name";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Employee Management</h2>
            
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
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">All Employees</h5>
                        <div>
                            <a href="create_employee.php" class="btn btn-success btn-sm">
                                <i class="bi bi-person-plus"></i> Create Employee
                            </a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Gender</th>
                                    <th>Hired Date</th>
                                    <th>Address</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($employee = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            echo htmlspecialchars($employee['first_name'] . ' ' . 
                                                ($employee['middle_name'] ? $employee['middle_name'] . ' ' : '') . 
                                                $employee['last_name']); 
                                            ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($employee['job_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($employee['email']); ?></td>
                                        <td><?php echo $employee['gender'] ? ucfirst(htmlspecialchars($employee['gender'])) : 'N/A'; ?></td>
                                        <td><?php echo $employee['hired_date'] ? date('M d, Y', strtotime($employee['hired_date'])) : 'N/A'; ?></td>
                                        <td><?php echo htmlspecialchars($employee['address_info'] ?? 'N/A'); ?></td>
                                        <td>
                                            <a href="edit_employee.php?id=<?php echo $employee['employee_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="employee_id" value="<?php echo $employee['employee_id']; ?>">
                                                <input type="hidden" name="action" value="update">
                                                <button type="submit" class="btn btn-sm <?php echo $employee['job_id'] === null ? 'btn-success' : 'btn-warning'; ?>"
                                                        onclick="return confirm('Are you sure you want to <?php echo $employee['job_id'] === null ? 'activate' : 'deactivate'; ?> this employee?')">
                                                    <i class="bi <?php echo $employee['job_id'] === null ? 'bi-person-check' : 'bi-person-x'; ?>"></i>
                                                    <?php echo $employee['job_id'] === null ? 'Activate' : 'Deactivate'; ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
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