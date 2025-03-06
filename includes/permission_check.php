<?php
function checkPermission($permission) {
    if (!isset($_SESSION[$permission]) || $_SESSION[$permission] !== true) {
        header("Location: ../auth/login.php");
        exit();
    }
}

function isMainAdmin() {
    return isset($_SESSION['is_main_admin']) && $_SESSION['is_main_admin'] === true;
}

function canManageAdmins() {
    return isset($_SESSION['can_manage_admins']) && $_SESSION['can_manage_admins'] === true;
}

function preventMainAdminModification($user_id) {
    // Get the user's role from database
    global $conn;
    $query = "SELECT j.job_name 
              FROM tbl_user u
              INNER JOIN tbl_employee e ON u.employee_id = e.employee_id 
              INNER JOIN tbl_jobs j ON e.job_id = j.job_id 
              WHERE u.user_id = ?";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        // If the target user is a Main Admin and current user is not a Main Admin
        if ($user['job_name'] === 'Main Admin' && !isMainAdmin()) {
            $_SESSION['error'] = "You cannot modify a Main Admin account.";
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit();
        }
    }
}

// Example usage in pages:
// require_once '../includes/permission_check.php';
// checkPermission('can_manage_users');
// if (canManageAdmins()) { /* show admin management options */ }
// preventMainAdminModification($user_id); // Call before any user modification
?> 