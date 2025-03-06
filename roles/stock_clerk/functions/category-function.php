<?php
// Handle category creation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $category_name = $_POST['category_name'];
        $category_description = $_POST['category_description'];
        $is_raw_ingredient = isset($_POST['is_raw_ingredient']) ? true : false;
        
        // Add RI_ prefix if it's a raw ingredient category
        if ($is_raw_ingredient) {
            $category_name = 'RI_' . $category_name;
        }
        
        // Check if category already exists
        $check_query = "SELECT COUNT(*) as count FROM tbl_categories WHERE category_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("s", $category_name);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['count'] > 0) {
            $error = "Category already exists.";
        } else {
            // Insert new category
            $query = "INSERT INTO tbl_categories (category_name, category_description, created_at) VALUES (?, ?, NOW())";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ss", $category_name, $category_description);
            
            if ($stmt->execute()) {
                // Log the transaction
                $description = "Created new " . ($is_raw_ingredient ? "raw ingredient " : "") . "category: " . 
                             ($is_raw_ingredient ? substr($category_name, 3) : $category_name) . 
                             " By: " . $_SESSION['employee_name'];
                $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                            VALUES ('category_create', ?, NOW())";
                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("s", $description);
                $log_stmt->execute();
                
                $success = "Category created successfully.";
            } else {
                $error = "Error creating category.";
            }
        }
    }
}

// Get all categories
$query = "SELECT * FROM tbl_categories ORDER BY category_name";
$result = $conn->query($query);
?>