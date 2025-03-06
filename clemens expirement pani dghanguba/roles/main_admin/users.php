<?php
session_start();
require_once '../../config/database.php';
require_once '../../includes/header.php';

// Check if user is Main Admin
if ($_SESSION['job_name'] !== 'Main Admin') {
    header("Location: " . $base_path . "/roles/" . strtolower($_SESSION['job_name']) . "/dashboard.php");
    exit();
}

$message = '';
$error = '';

// Handle user status updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $user_id = $_POST['user_id'];
    
    // Verify the user is not an admin or main admin before updating
    $stmt = $conn->prepare("SELECT u.user_id FROM tbl_user u 
                           INNER JOIN tbl_employee e ON u.employee_id = e.employee_id
                           INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
                           WHERE u.user_id = ? AND j.job_name NOT IN ('Main Admin', 'Admin')");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Get user details for logging
        $stmt = $conn->prepare("SELECT e.first_name, e.last_name 
                              FROM tbl_user u
                              INNER JOIN tbl_employee e ON u.employee_id = e.employee_id
                              WHERE u.user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $user_result = $stmt->get_result();
        $user = $user_result->fetch_assoc();
        
        // Log the action
        $description = "Updated status for user {$user['first_name']} {$user['last_name']}";
        $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (employee_id, action_type, description) VALUES (?, 'update_user', ?)");
        $stmt->bind_param("is", $_SESSION['employee_id'], $description);
        
        if ($stmt->execute()) {
            $message = "User status updated successfully.";
        } else {
            $error = "Error updating user status.";
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

<div class="container-fluid">
    <div class="row mb-3">
        <div class="col">
            <h2>User Management</h2>
        </div>
        <div class="col text-end">
            <a href="create_user.php" class="btn btn-primary">
                <i class="bi bi-person-plus"></i> Create New User
            </a>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Created</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows > 0): ?>
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
                                    <td><?php echo htmlspecialchars($user['job_name']); ?></td>
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
                                            <button type="submit" class="btn btn-sm <?php echo $is_active ? 'btn-warning' : 'btn-success'; ?>"
                                                    onclick="return confirm('Are you sure you want to <?php echo $is_active ? 'deactivate' : 'activate'; ?> this user?')">
                                                <i class="bi <?php echo $is_active ? 'bi-person-x' : 'bi-person-check'; ?>"></i>
                                                <?php echo $is_active ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7" class="text-center">No users found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 