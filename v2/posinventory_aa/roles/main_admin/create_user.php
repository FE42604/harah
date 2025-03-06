<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Main Admin
if (!isset($_SESSION['user_id']) || $_SESSION['job_name'] !== 'Main Admin') {
    header("Location: ../../auth/login.php");
    exit();
}

$message = '';
$error = '';

// Get creator's information for logging
$creator_query = "SELECT first_name, last_name FROM tbl_employee WHERE employee_id = ?";
$stmt = $conn->prepare($creator_query);
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$creator_result = $stmt->get_result();
$creator = $creator_result->fetch_assoc();
$creator_name = $creator['first_name'] . ' ' . $creator['last_name'];

// Get all employees without user accounts (Cashier, Stock Clerk, and Admin)
$query = "SELECT e.*, j.job_name 
          FROM tbl_employee e
          INNER JOIN tbl_jobs j ON e.job_id = j.job_id
          LEFT JOIN tbl_user u ON e.employee_id = u.employee_id
          WHERE u.user_id IS NULL AND j.job_id IN (1, 2, 6)
          ORDER BY e.last_name, e.first_name";
$result = $conn->query($query);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $_POST['employee_id'];
    $username = $_POST['username'];
    $password = sha1($_POST['password']);
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Verify employee is Cashier, Stock Clerk, or Admin
        $verify_query = "SELECT j.job_name 
                        FROM tbl_employee e
                        INNER JOIN tbl_jobs j ON e.job_id = j.job_id
                        WHERE e.employee_id = ? AND j.job_id IN (1, 2, 6)";
        $stmt = $conn->prepare($verify_query);
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $verify_result = $stmt->get_result();
        
        if ($verify_result->num_rows === 0) {
            throw new Exception("Unauthorized: Can only create user accounts for Cashier, Stock Clerk, or Admin roles");
        }
        
        // Insert new user
        $stmt = $conn->prepare("INSERT INTO tbl_user (employee_id, user_name, user_password) VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $employee_id, $username, $password);
        
        if ($stmt->execute()) {
            // Get the created user's information for logging
            $user_query = "SELECT e.first_name, e.last_name, j.job_name 
                          FROM tbl_employee e
                          INNER JOIN tbl_jobs j ON e.job_id = j.job_id
                          WHERE e.employee_id = ?";
            $stmt = $conn->prepare($user_query);
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $user_result = $stmt->get_result();
            $user = $user_result->fetch_assoc();
            
            // Create transaction log entry
            $description = "Created new user account for {$user['first_name']} {$user['last_name']} as {$user['job_name']} (by {$creator_name})";
            $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) VALUES ('create_user', ?, NOW())");
            $stmt->bind_param("s", $description);
            
            if ($stmt->execute()) {
                $conn->commit();
                $message = "User account created successfully.";
            } else {
                throw new Exception("Error creating transaction log");
            }
        } else {
            throw new Exception("Error creating user account");
        }
    } catch (Exception $e) {
        $conn->rollback();
        $error = $e->getMessage();
    }
}
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">Create User Account</h2>
            
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
                    <h5 class="mb-0">New User Account</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="mb-3">
                            <label for="employee_id" class="form-label">Select Employee</label>
                            <select class="form-select" id="employee_id" name="employee_id" required>
                                <option value="">Choose an employee...</option>
                                <?php while ($employee = $result->fetch_assoc()): ?>
                                    <option value="<?php echo $employee['employee_id']; ?>">
                                        <?php 
                                        echo htmlspecialchars($employee['first_name'] . ' ' . 
                                            ($employee['middle_name'] ? $employee['middle_name'] . ' ' : '') . 
                                            $employee['last_name'] . ' (' . $employee['job_name'] . ')'); 
                                        ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="d-flex justify-content-end gap-2">
                            <a href="users.php" class="btn btn-secondary">Cancel</a>
                            <button type="submit" class="btn btn-primary">Create User Account</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 