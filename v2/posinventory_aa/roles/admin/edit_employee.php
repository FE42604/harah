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

// Get employee ID from URL
$employee_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$employee_id) {
    header("Location: employees.php");
    exit();
}

// Get available job roles (excluding Main Admin and Admin)
$stmt = $conn->prepare("SELECT job_id, job_name FROM tbl_jobs WHERE job_name NOT IN ('Main Admin', 'Admin') ORDER BY job_name");
$stmt->execute();
$jobs = $stmt->get_result();

// Get employee data and verify they are not an admin/main admin
$stmt = $conn->prepare("SELECT e.*, j.job_name 
                       FROM tbl_employee e 
                       JOIN tbl_jobs j ON e.job_id = j.job_id 
                       WHERE e.employee_id = ? AND j.job_name NOT IN ('Main Admin', 'Admin')");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();
$employee = $result->fetch_assoc();

if (!$employee) {
    header("Location: employees.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $hired_date = $_POST['hired_date'];
    $address_info = trim($_POST['address_info']);
    $job_id = $_POST['job_id'];

    // Validate input
    if (empty($first_name) || empty($last_name) || empty($email) || empty($gender) || 
        empty($hired_date) || empty($address_info) || empty($job_id)) {
        $error = "All fields except middle name are required.";
    } else {
        // Verify the selected job is not admin or main admin
        $stmt = $conn->prepare("SELECT job_id FROM tbl_jobs WHERE job_id = ? AND job_name NOT IN ('Main Admin', 'Admin')");
        $stmt->bind_param("i", $job_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $error = "Invalid job role selected.";
        } else {
            // Check if email already exists for other employees
            $stmt = $conn->prepare("SELECT employee_id FROM tbl_employee WHERE email = ? AND employee_id != ?");
            $stmt->bind_param("si", $email, $employee_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Email already exists.";
            } else {
                // Begin transaction
                $conn->begin_transaction();

                try {
                    // Update employee table
                    $stmt = $conn->prepare("UPDATE tbl_employee SET 
                        first_name = ?, 
                        middle_name = ?, 
                        last_name = ?, 
                        email = ?, 
                        gender = ?, 
                        hired_date = ?, 
                        address_info = ?, 
                        job_id = ? 
                        WHERE employee_id = ?");
                    $stmt->bind_param("sssssssii", 
                        $first_name, $middle_name, $last_name, $email, 
                        $gender, $hired_date, $address_info, $job_id, $employee_id);
                    $stmt->execute();

                    // Get job name for logging
                    $stmt = $conn->prepare("SELECT job_name FROM tbl_jobs WHERE job_id = ?");
                    $stmt->bind_param("i", $job_id);
                    $stmt->execute();
                    $job_result = $stmt->get_result();
                    $job = $job_result->fetch_assoc();

                    // Log the action
                    $description = "Updated employee: $first_name $last_name (ID: $employee_id) - New role: {$job['job_name']}";
                    $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (employee_id, action_type, description) VALUES (?, 'update_employee', ?)");
                    $stmt->bind_param("is", $_SESSION['employee_id'], $description);
                    $stmt->execute();

                    $conn->commit();
                    $message = "Employee updated successfully!";
                    
                    // Refresh employee data
                    $stmt = $conn->prepare("SELECT e.*, j.job_name 
                                          FROM tbl_employee e 
                                          JOIN tbl_jobs j ON e.job_id = j.job_id 
                                          WHERE e.employee_id = ? AND j.job_name NOT IN ('Main Admin', 'Admin')");
                    $stmt->bind_param("i", $employee_id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $employee = $result->fetch_assoc();
                } catch (Exception $e) {
                    $conn->rollback();
                    $error = "Error updating employee. Please try again.";
                }
            }
        }
    }
}
?>

<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Edit Employee</h4>
                </div>
                <div class="card-body">
                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo $message; ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?php echo htmlspecialchars($employee['first_name']); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middle_name" name="middle_name"
                                       value="<?php echo htmlspecialchars($employee['middle_name']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name"
                                       value="<?php echo htmlspecialchars($employee['last_name']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" name="email"
                                   value="<?php echo htmlspecialchars($employee['email']); ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="gender" class="form-label">Gender</label>
                                <select class="form-select" id="gender" name="gender" required>
                                    <option value="">Select gender</option>
                                    <option value="male" <?php echo $employee['gender'] === 'male' ? 'selected' : ''; ?>>Male</option>
                                    <option value="female" <?php echo $employee['gender'] === 'female' ? 'selected' : ''; ?>>Female</option>
                                    <option value="other" <?php echo $employee['gender'] === 'other' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="hired_date" class="form-label">Hired Date</label>
                                <input type="date" class="form-control" id="hired_date" name="hired_date"
                                       value="<?php echo htmlspecialchars($employee['hired_date']); ?>" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="address_info" class="form-label">Address</label>
                            <textarea class="form-control" id="address_info" name="address_info" rows="2" required><?php echo htmlspecialchars($employee['address_info']); ?></textarea>
                        </div>

                        <div class="mb-3">
                            <label for="job_id" class="form-label">Job Role</label>
                            <select class="form-select" id="job_id" name="job_id" required>
                                <option value="">Select a role</option>
                                <?php 
                                $jobs->data_seek(0);
                                while ($job = $jobs->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $job['job_id']; ?>" 
                                            <?php echo $employee['job_id'] == $job['job_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($job['job_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">Update Employee</button>
                            <a href="employees.php" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../../includes/footer.php'; ?> 