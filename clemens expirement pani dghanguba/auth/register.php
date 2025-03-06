<?php
session_start();
require_once '../config/database.php';

// Only Main Admin and Admin can access this page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['job_name'], ['Main Admin', 'Admin'])) {
    header("Location: login.php");
    exit();
}

// Get employees without accounts who are eligible for one
$query = "SELECT e.*, j.job_name 
          FROM tbl_employee e 
          INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
          LEFT JOIN tbl_user u ON e.employee_id = u.employee_id 
          WHERE u.user_id IS NULL 
          AND j.job_name IN ('Main Admin', 'Admin', 'Cashier', 'Stock Clerk')";

// If not Main Admin, exclude Admin role from results
if ($_SESSION['job_name'] !== 'Main Admin') {
    $query .= " AND j.job_name != 'Admin'";
}

$query .= " ORDER BY e.last_name, e.first_name";

$result = $conn->query($query);
$eligible_employees = [];
while ($row = $result->fetch_assoc()) {
    $eligible_employees[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create User Account - Sales and Inventory System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Create User Account</h4>
                        <button class="btn btn-light" onclick="window.location.href='../index.php'">Back to Dashboard</button>
                    </div>
                    <div class="card-body">
                        <?php if (empty($eligible_employees)): ?>
                        <div class="alert alert-info">
                            No eligible employees found. Please create employees first in the Employee Management section.
                        </div>
                        <?php else: ?>
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Job Title</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($eligible_employees as $employee): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?></td>
                                    <td><?php echo htmlspecialchars($employee['job_name']); ?></td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" 
                                                onclick="showCreateAccountModal(<?php echo $employee['employee_id']; ?>, 
                                                '<?php echo htmlspecialchars($employee['first_name'] . ' ' . $employee['last_name']); ?>')">
                                            Create Account
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Account Modal -->
    <div class="modal fade" id="createAccountModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">Create Account</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createAccountForm" method="POST" action="process_register.php">
                    <div class="modal-body">
                        <input type="hidden" id="employeeId" name="employee_id">
                        <p>Creating account for: <span id="employeeName"></span></p>
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" class="form-control" id="username" name="username" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Create Account</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Error/Success Modal -->
    <div class="modal fade" id="messageModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p id="messageText"></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showCreateAccountModal(employeeId, employeeName) {
            document.getElementById('employeeId').value = employeeId;
            document.getElementById('employeeName').textContent = employeeName;
            new bootstrap.Modal(document.getElementById('createAccountModal')).show();
        }

        <?php if (isset($_SESSION['message'])): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const messageModal = new bootstrap.Modal(document.getElementById('messageModal'));
            document.getElementById('messageText').textContent = "<?php echo $_SESSION['message']; ?>";
            messageModal.show();
            <?php unset($_SESSION['message']); ?>
        });
        <?php endif; ?>

        document.getElementById('createAccountForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const username = document.getElementById('username').value;

            if (password !== confirmPassword) {
                e.preventDefault();
                alert('Passwords do not match!');
                return;
            }

            if (username.trim() === '') {
                e.preventDefault();
                alert('Username is required!');
                return;
            }
        });
    </script>
</body>
</html> 