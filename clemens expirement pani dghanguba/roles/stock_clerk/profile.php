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

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_password = sha1($_POST['current_password']);
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify current password
    $stmt = $conn->prepare("SELECT user_password FROM tbl_user WHERE user_id = ?");
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($current_password !== $user['user_password']) {
        $error = "Current password is incorrect.";
    } elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 6) {
        $error = "New password must be at least 6 characters long.";
    } else {
        // Update password
        $new_password_hash = sha1($new_password);
        $stmt = $conn->prepare("UPDATE tbl_user SET user_password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_password_hash, $_SESSION['user_id']);
        
        if ($stmt->execute()) {
            $message = "Password updated successfully.";
        } else {
            $error = "Error updating password.";
        }
    }
}

// Get user information
$query = "SELECT e.*, j.job_name, u.user_name 
          FROM tbl_employee e 
          INNER JOIN tbl_user u ON e.employee_id = u.employee_id
          INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
          WHERE e.employee_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $_SESSION['employee_id']);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: ../../auth/login.php");
    exit();
}
?>

<div class="container-fluid py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
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

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Profile Information</h5>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-3 text-center">
                            <div class="profile-image mb-3">
                                <i class="bi bi-person-circle display-1"></i>
                            </div>
                            <h5 class="mb-0"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h5>
                            <p class="text-muted mb-0"><?php echo htmlspecialchars($user['job_name']); ?></p>
                        </div>
                        <div class="col-md-9">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Username</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['user_name']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">First Name</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['first_name']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Last Name</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['last_name']); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Middle Name</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['middle_name'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Email</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Gender</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['gender'] ?? 'N/A'); ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Hired Date</label>
                                    <p class="mb-0"><?php echo $user['hired_date'] ? date('F d, Y', strtotime($user['hired_date'])) : 'N/A'; ?></p>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label text-muted">Address</label>
                                    <p class="mb-0"><?php echo htmlspecialchars($user['address_info'] ?? 'N/A'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Change Password</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Current Password</label>
                                <input type="password" class="form-control" name="current_password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">New Password</label>
                                <input type="password" class="form-control" name="new_password" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Confirm New Password</label>
                                <input type="password" class="form-control" name="confirm_password" required>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="change_password" class="btn btn-primary">Change Password</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 