<?php
session_start();
require_once '../config/database.php';

// Only Main Admin and Admin can access this page
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['job_name'], ['Main Admin', 'Admin'])) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $employee_id = $conn->real_escape_string($_POST['employee_id']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    if ($password !== $confirm_password) {
        $_SESSION['message'] = "Passwords do not match!";
        header("Location: register.php");
        exit();
    }

    // Check if username already exists
    $check_username = "SELECT user_id FROM tbl_user WHERE user_name = ?";
    $stmt = $conn->prepare($check_username);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $_SESSION['message'] = "Username already exists. Please choose a different username.";
        header("Location: register.php");
        exit();
    }

    // Check if employee exists and is eligible for an account
    $query = "SELECT e.*, j.job_name 
              FROM tbl_employee e 
              INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
              LEFT JOIN tbl_user u ON e.employee_id = u.employee_id 
              WHERE e.employee_id = ? AND u.user_id IS NULL";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $employee = $result->fetch_assoc();
        
        // Check if the job title allows for an account
        $allowedJobs = ['Main Admin', 'Admin', 'Cashier', 'Stock Clerk'];
        
        if (!in_array($employee['job_name'], $allowedJobs)) {
            $_SESSION['message'] = "This employee's job role does not allow for system access.";
            header("Location: register.php");
            exit();
        }

        // If creating an Admin account, check if current user is Main Admin
        if ($employee['job_name'] === 'Admin' && $_SESSION['job_name'] !== 'Main Admin') {
            $_SESSION['message'] = "Only Main Admin can create Admin accounts.";
            header("Location: register.php");
            exit();
        }

        // Create the user account with SHA1 hashed password
        $hashed_password = sha1($password);
        $insert_query = "INSERT INTO tbl_user (employee_id, user_name, user_password, user_created) VALUES (?, ?, ?, NOW())";
        
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("iss", $employee_id, $username, $hashed_password);
        
        if ($insert_stmt->execute()) {
            $_SESSION['message'] = "Account created successfully!";
        } else {
            $_SESSION['message'] = "Error creating account. Please try again.";
        }
    } else {
        $_SESSION['message'] = "Employee not found or already has an account.";
    }
}

header("Location: register.php");
exit();
?> 