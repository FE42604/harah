<?php
session_start();
require_once '../config/database.php';

// Check if user is in timeout
if (isset($_SESSION['login_attempts']) && $_SESSION['login_attempts'] >= 3) {
    if (isset($_SESSION['last_attempt_time'])) {
        $time_elapsed = time() - $_SESSION['last_attempt_time'];
        if ($time_elapsed < 30) {
            $_SESSION['error'] = "Too many failed attempts. Please wait 30 seconds before trying again.";
            header("Location: login.php");
            exit();
        } else {
            // Reset attempts after timeout
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt_time']);
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $conn->real_escape_string($_POST['username']);
    $password = sha1($_POST['password']);

    // First check if the user exists and has the right job title
    $query = "SELECT e.*, j.job_name, u.user_id, u.user_password 
              FROM tbl_user u
              INNER JOIN tbl_employee e ON u.employee_id = e.employee_id 
              INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
              WHERE u.user_name = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if the job title allows for an account
        $allowedJobs = ['Main Admin', 'Admin', 'Cashier', 'Stock Clerk'];
        
        if (!in_array($user['job_name'], $allowedJobs)) {
            $_SESSION['error'] = "Your job role does not have system access privileges.";
            header("Location: login.php");
            exit();
        }

        // Verify password using SHA1 comparison
        if ($password === $user['user_password']) {
            // Successful login - reset attempts
            unset($_SESSION['login_attempts']);
            unset($_SESSION['last_attempt_time']);
            
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['employee_id'] = $user['employee_id'];
            $_SESSION['job_name'] = $user['job_name'];
            $_SESSION['full_name'] = $user['first_name'] . ' ' . $user['last_name'];
            
            // Log successful login
            $description = "User " . $user['first_name'] . " " . $user['last_name'] . " logged in successfully";
            $stmt = $conn->prepare("INSERT INTO tbl_transaction_log (transaction_type, transaction_description) VALUES ('Login', ?)");
            $stmt->bind_param("s", $description);
            $stmt->execute();
            
            // Add role-based access control flags
            if ($user['job_name'] === 'Main Admin') {
                $_SESSION['is_main_admin'] = true;
                $_SESSION['can_manage_admins'] = true;
                $_SESSION['can_manage_users'] = true;
                $_SESSION['can_manage_inventory'] = true;
                $_SESSION['can_manage_sales'] = true;
                $_SESSION['can_manage_employees'] = true;
                $_SESSION['can_view_reports'] = true;
            } elseif ($user['job_name'] === 'Admin') {
                $_SESSION['is_main_admin'] = false;
                $_SESSION['can_manage_admins'] = false;
                $_SESSION['can_manage_users'] = true;
                $_SESSION['can_manage_inventory'] = true;
                $_SESSION['can_manage_sales'] = true;
                $_SESSION['can_manage_employees'] = true;
                $_SESSION['can_view_reports'] = true;
            }
            
            // Remove last_login update temporarily
            
            // Redirect based on role
            switch ($user['job_name']) {
                case 'Main Admin':
                    header("Location: ../roles/main_admin/dashboard.php");
                    break;
                case 'Admin':
                    header("Location: ../roles/admin/dashboard.php");
                    break;
                case 'Cashier':
                    header("Location: ../roles/cashier/dashboard.php");
                    break;
                case 'Stock Clerk':
                    header("Location: ../roles/stock_clerk/dashboard.php");
                    break;
                default:
                    header("Location: login.php");
            }
            exit();
        } else {
            // Failed login - increment attempts
            $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
            $_SESSION['last_attempt_time'] = time();
            
            $_SESSION['error'] = "Invalid username or password.";
            header("Location: login.php");
            exit();
        }
    } else {
        // Failed login - increment attempts
        $_SESSION['login_attempts'] = isset($_SESSION['login_attempts']) ? $_SESSION['login_attempts'] + 1 : 1;
        $_SESSION['last_attempt_time'] = time();
        
        $_SESSION['error'] = "Invalid username or password.";
        header("Location: login.php");
        exit();
    }
}

header("Location: login.php");
exit();
?> 