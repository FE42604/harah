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

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    
    // Verify the user is not an admin or main admin before updating
    $stmt = $conn->prepare("SELECT u.user_id, u.user_name, u.user_password, e.first_name, e.last_name, j.job_name 
                           FROM tbl_user u 
                           INNER JOIN tbl_employee e ON u.employee_id = e.employee_id
                           INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
                           WHERE u.user_id = ? AND j.job_name NOT IN ('Main Admin', 'Admin')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $conn->begin_transaction();
        
        try {
            // Check if password starts with 'INACTIVE_'
            $is_active = strpos($user['user_password'], 'INACTIVE_') !== 0;
            
            if ($is_active) {
                // Deactivate by prepending 'INACTIVE_' to password
                $new_password = 'INACTIVE_' . $user['user_password'];
                $status_text = 'deactivated';
            } else {
                // Activate by removing 'INACTIVE_' prefix
                $new_password = substr($user['user_password'], 9);
                $status_text = 'activated';
            }
            
            // Update password
            $stmt = $conn->prepare("UPDATE tbl_user SET user_password = ? WHERE user_id = ?");
            $stmt->bind_param("si", $new_password, $user_id);
            
            if ($stmt->execute()) {
                // Log the action
                $description = "User {$user['first_name']} {$user['last_name']} was {$status_text}";
                $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) VALUES ('update_user', ?, NOW())");
                $stmt->bind_param("s", $description);
                $stmt->execute();
                
                $conn->commit();
                $message = "User status updated successfully.";
            } else {
                throw new Exception("Error updating user status");
            }
        } catch (Exception $e) {
            $conn->rollback();
            $error = $e->getMessage();
        }
    } else {
        $error = "Unauthorized action.";
    }
}

// Get all non-admin users with their employee and job information
$query = "SELECT u.user_id, u.user_name, u.user_password, u.user_created, 
          e.first_name, e.middle_name, e.last_name, e.email, j.job_name 
          FROM tbl_user u 
          INNER JOIN tbl_employee e ON u.employee_id = e.employee_id
          INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
          WHERE j.job_name NOT IN ('Main Admin', 'Admin')
          ORDER BY j.job_name, e.last_name, e.first_name";
$result = $conn->query($query);
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-4">User Management</h2>
            
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
                        <h5 class="mb-0">All Users</h5>
                        <div>
                            <a href="create_user.php" class="btn btn-success btn-sm">
                                <i class="bi bi-person-plus"></i> Create User
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
                                    <th>Username</th>
                                    <th>Role</th>
                                    <th>Email</th>
                                    <th>Created Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($user = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            echo htmlspecialchars($user['first_name'] . ' ' . 
                                                ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . 
                                                $user['last_name']); 
                                            ?>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                        <td>
                                            <span class="badge bg-info">
                                                <?php echo htmlspecialchars($user['job_name']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($user['user_created'])); ?></td>
                                        <td>
                                            <?php $is_active = strpos($user['user_password'], 'INACTIVE_') !== 0; ?>
                                            <span class="badge <?php echo $is_active ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo $is_active ? 'Active' : 'Inactive'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="edit_user.php?id=<?php echo $user['user_id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                            <form method="POST" action="" class="d-inline">
                                                <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                <input type="hidden" name="action" value="update">
                                                <?php $is_active = strpos($user['user_password'], 'INACTIVE_') !== 0; ?>
                                                <button type="submit" class="btn btn-sm <?php echo $is_active ? 'btn-warning' : 'btn-success'; ?>"
                                                        onclick="return confirm('Are you sure you want to <?php echo $is_active ? 'deactivate' : 'activate'; ?> this user?')">
                                                    <i class="bi <?php echo $is_active ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                                                    <?php echo $is_active ? 'Deactivate' : 'Activate'; ?>
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