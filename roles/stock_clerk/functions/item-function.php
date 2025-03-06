<?php

// Handle item operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'create':
            $name = $_POST['name'];
            $description = $_POST['description'];
            $supplier_id = $_POST['supplier_id'];
            $category_id = $_POST['category_id'];
            $cost = $_POST['cost'];
            $employee_id = $_SESSION['employee_id'];
            
            // Check if item already exists
            $check_query = "SELECT COUNT(*) as count FROM tbl_item_list WHERE name = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("s", $name);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $_SESSION['error'] = "Item already exists.";
            } else {
                // Insert new item
                $query = "INSERT INTO tbl_item_list (name, description, supplier_id, employee_id, category_id, cost, date_created) 
                         VALUES (?, ?, ?, ?, ?, ?, NOW())";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssiiid", $name, $description, $supplier_id, $employee_id, $category_id, $cost);
                
                if ($stmt->execute()) {
                    // Log the transaction
                    $description = "Added new item: $name By: " . $_SESSION['employee_name'];
                    $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                                VALUES ('', ?, NOW())";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->bind_param("s", $description);
                    $log_stmt->execute();
                    
                    $_SESSION['success'] = "Item created successfully.";
                } else {
                    $_SESSION['error'] = "Error creating item: " . $conn->error;
                }
            }
            break;

        case 'edit':
            $item_id = $_POST['item_id'];
            $name = $_POST['name'];
            $description = $_POST['description'];
            $supplier_id = $_POST['supplier_id'];
            $category_id = $_POST['category_id'];
            $cost = $_POST['cost'];
            
            // Check if the new name already exists for other items
            $check_query = "SELECT COUNT(*) as count FROM tbl_item_list WHERE name = ? AND item_id != ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("si", $name, $item_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();
            $row = $result->fetch_assoc();
            
            if ($row['count'] > 0) {
                $_SESSION['error'] = "An item with this name already exists.";
            } else {
                // Update item
                $query = "UPDATE tbl_item_list 
                         SET name = ?, 
                             description = ?, 
                             supplier_id = ?, 
                             category_id = ?, 
                             cost = ?,
                             date_updated = NOW() 
                         WHERE item_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("ssiidi", $name, $description, $supplier_id, $category_id, $cost, $item_id);
                
                if ($stmt->execute()) {
                    // Log the transaction
                    $description = "Updated item: $name By: " . $_SESSION['employee_name'];
                    $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                                VALUES ('', ?, NOW())";
                    $log_stmt = $conn->prepare($log_query);
                    $log_stmt->bind_param("s", $description);
                    $log_stmt->execute();
                    
                    $_SESSION['success'] = "Item updated successfully.";
                } else {
                    $_SESSION['error'] = "Error updating item: " . $conn->error;
                }
            }
            break;
    }
    header("Location: ../items.php");
    exit();
}

// Handle GET requests (like toggle status)
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'toggle_status') {
    $item_id = $_GET['item_id'];
    
    // First get current status and name
    $status_query = "SELECT status, name FROM tbl_item_list WHERE item_id = ?";
    $status_stmt = $conn->prepare($status_query);
    $status_stmt->bind_param("i", $item_id);
    $status_stmt->execute();
    $result = $status_stmt->get_result();
    $item = $result->fetch_assoc();
    
    // Toggle the status
    $new_status = $item['status'] === 'Active' ? 'Inactive' : 'Active';
    
    $query = "UPDATE tbl_item_list SET status = ?, date_updated = NOW() WHERE item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $new_status, $item_id);
    
    if ($stmt->execute()) {
        // Log the transaction
        $action = $new_status === 'Active' ? 'activated' : 'deactivated';
        $description = "Item {$item['name']} was $action By: " . $_SESSION['employee_name'];
        $log_query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
                    VALUES ('', ?, NOW())";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("s", $description);
        $log_stmt->execute();
        
        $_SESSION['success'] = "Item status updated successfully.";
    } else {
        $_SESSION['error'] = "Error updating item status: " . $conn->error;
    }
    
    header("Location: ../items.php");
    exit();
}

// Get all items with joined data
$query = "SELECT i.*, 
          c.category_name,
          s.supplier_name,
          CONCAT(e.first_name, ' ', e.last_name) as employee_name
          FROM tbl_item_list i
          LEFT JOIN tbl_categories c ON i.category_id = c.category_id
          LEFT JOIN tbl_suppliers s ON i.supplier_id = s.supplier_id
          LEFT JOIN tbl_employee e ON i.employee_id = e.employee_id
          ORDER BY i.name";
$result = $conn->query($query);

?> 