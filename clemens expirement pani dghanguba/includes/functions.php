<?php
/**
 * Helper functions for the inventory management system
 */

/**
 * Get employee full name by ID
 */
function getEmployeeName($conn, $employee_id) {
    $query = "SELECT CONCAT(first_name, ' ', last_name) as full_name FROM tbl_employee WHERE employee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $employee = $result->fetch_assoc();
    return $employee['full_name'];
}

/**
 * Log a transaction
 */
function logTransaction($conn, $type, $description) {
    $query = "INSERT INTO tbl_transaction_log (transaction_type, transaction_description, transaction_date) 
              VALUES (?, ?, NOW())";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $type, $description);
    return $stmt->execute();
}

/**
 * Update inventory quantity for a raw ingredient
 */
function updateInventoryQuantity($conn, $raw_ingredient_id, $quantity_change, $type) {
    $query = "UPDATE tbl_raw_ingredients 
              SET current_stock = current_stock " . ($type === 'add' ? '+' : '-') . " ? 
              WHERE raw_ingredient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $quantity_change, $raw_ingredient_id);
    $stmt->execute();
}

/**
 * Get all items for a purchase order
 */
function getPurchaseOrderItems($conn, $order_id) {
    $query = "SELECT pi.*, il.name, ri.raw_ingredient_id 
              FROM tbl_purchase_items pi 
              JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id 
              JOIN tbl_item_list il ON ri.item_id = il.item_id 
              WHERE pi.purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get details of a back order
 */
function getBackOrderDetails($conn, $back_order_id) {
    $query = "SELECT bol.*, pi.purchase_order_id, pi.purchase_item_id 
              FROM tbl_back_order_list bol 
              JOIN tbl_purchase_items pi ON bol.purchase_item_id = pi.purchase_item_id 
              WHERE bol.back_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $back_order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Get details of a return
 */
function getReturnDetails($conn, $return_id) {
    $query = "SELECT rl.*, pi.purchase_order_id, pi.purchase_item_id 
              FROM tbl_return_list rl 
              JOIN tbl_purchase_items pi ON rl.purchase_item_id = pi.purchase_item_id 
              WHERE rl.return_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $return_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Update purchase order status
 */
function updatePurchaseOrderStatus($conn, $order_id, $status) {
    $query = "UPDATE tbl_purchase_order_list SET status = ? WHERE purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("si", $status, $order_id);
    return $stmt->execute();
}

/**
 * Check if a purchase order is complete
 */
function checkPurchaseOrderCompletion($conn, $order_id) {
    $query = "SELECT COUNT(*) as total, 
                     SUM(CASE WHEN quantity_received >= quantity_ordered THEN 1 ELSE 0 END) as received,
                     SUM(CASE WHEN back_ordered_quantity > 0 THEN 1 ELSE 0 END) as back_ordered
              FROM tbl_purchase_items 
              WHERE purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = $result->fetch_assoc();
    
    if ($counts['total'] == $counts['received']) {
        return 'received';
    } elseif ($counts['back_ordered'] > 0) {
        return 'back_ordered';
    } elseif ($counts['received'] > 0) {
        return 'partially_received';
    } else {
        return 'ordered';
    }
}

/**
 * Validate return quantity
 */
function validateReturnQuantity($conn, $purchase_item_id, $quantity_returned) {
    $query = "SELECT quantity_received FROM tbl_purchase_items WHERE purchase_item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    return $quantity_returned <= $item['quantity_received'];
}

/**
 * Validate back order quantity
 */
function validateBackOrderQuantity($conn, $purchase_item_id, $quantity_back_ordered) {
    $query = "SELECT quantity_ordered, quantity_received, back_ordered_quantity 
              FROM tbl_purchase_items 
              WHERE purchase_item_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_item_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $item = $result->fetch_assoc();
    $remaining = $item['quantity_ordered'] - $item['quantity_received'] - $item['back_ordered_quantity'];
    return $quantity_back_ordered <= $remaining;
}

/**
 * Get item details
 */
function getItemDetails($conn, $raw_ingredient_id) {
    $query = "SELECT ri.*, il.name 
              FROM tbl_raw_ingredients ri 
              JOIN tbl_item_list il ON ri.item_id = il.item_id 
              WHERE ri.raw_ingredient_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $raw_ingredient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

/**
 * Update purchase order total amount
 */
function updatePurchaseOrderTotal($conn, $order_id) {
    $query = "UPDATE tbl_purchase_order_list pol 
              SET total_amount = (
                  SELECT COALESCE(SUM(pi.quantity_ordered * ri.unit_price), 0)
                  FROM tbl_purchase_items pi
                  JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id
                  WHERE pi.purchase_order_id = ?
              )
              WHERE pol.purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $order_id, $order_id);
    $stmt->execute();
}

/**
 * Check stock alerts
 */
function checkStockAlerts($conn) {
    $query = "SELECT ri.*, il.name, il.unit_of_measure
              FROM tbl_raw_ingredients ri
              JOIN tbl_item_list il ON ri.item_id = il.item_id
              WHERE ri.current_stock <= ri.reorder_point
              ORDER BY ri.current_stock ASC";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Format currency
 */
function formatCurrency($amount) {
    return '₱' . number_format($amount, 2);
}

/**
 * Format date
 */
function formatDate($date) {
    return date('M d, Y', strtotime($date));
}

/**
 * Get status badge class
 */
function getStatusBadgeClass($status) {
    switch ($status) {
        case 'ordered':
            return 'warning';
        case 'partially_received':
            return 'info';
        case 'received':
            return 'success';
        case 'back_ordered':
            return 'danger';
        case 'cancelled':
            return 'secondary';
        default:
            return 'primary';
    }
}

/**
 * Get status text
 */
function getStatusText($status) {
    return ucfirst(str_replace('_', ' ', $status));
}

/**
 * Sanitize input
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input));
}

/**
 * Validate date
 */
function validateDate($date) {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

/**
 * Get current date
 */
function getCurrentDate() {
    return date('Y-m-d');
}

/**
 * Get current timestamp
 */
function getCurrentTimestamp() {
    return date('Y-m-d H:i:s');
}

/**
 * Get purchase order details
 */
function getPurchaseOrderDetails($conn, $order_id) {
    $query = "SELECT pol.*, s.supplier_name, e.first_name, e.last_name 
              FROM tbl_purchase_order_list pol 
              JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
              JOIN tbl_employee e ON pol.employee_id = e.employee_id 
              WHERE pol.purchase_order_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get supplier details
 */
function getSupplierDetails($conn, $supplier_id) {
    $query = "SELECT * FROM tbl_suppliers WHERE supplier_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get all suppliers
 */
function getAllSuppliers($conn) {
    $query = "SELECT * FROM tbl_suppliers ORDER BY supplier_name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all raw ingredients
 */
function getAllRawIngredients($conn) {
    $query = "SELECT ri.*, il.name, il.unit_of_measure 
              FROM tbl_raw_ingredients ri 
              JOIN tbl_item_list il ON ri.item_id = il.item_id 
              ORDER BY il.name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get all categories
 */
function getAllCategories($conn) {
    $query = "SELECT * FROM tbl_categories ORDER BY category_name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get items by category
 */
function getItemsByCategory($conn, $category_id) {
    $query = "SELECT il.*, c.category_name 
              FROM tbl_item_list il 
              JOIN tbl_categories c ON il.category_id = c.category_id 
              WHERE il.category_id = ? 
              ORDER BY il.name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get items by supplier
 */
function getItemsBySupplier($conn, $supplier_id) {
    $query = "SELECT il.*, s.supplier_name 
              FROM tbl_item_list il 
              JOIN tbl_suppliers s ON il.supplier_id = s.supplier_id 
              WHERE il.supplier_id = ? 
              ORDER BY il.name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get receiving history
 */
function getReceivingHistory($conn, $purchase_item_id) {
    $query = "SELECT rl.*, e.first_name, e.last_name 
              FROM tbl_receiving_list rl 
              JOIN tbl_employee e ON rl.employee_id = e.employee_id 
              WHERE rl.purchase_item_id = ? 
              ORDER BY rl.receiving_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_item_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get return history
 */
function getReturnHistory($conn, $purchase_item_id) {
    $query = "SELECT rl.*, e.first_name, e.last_name 
              FROM tbl_return_list rl 
              JOIN tbl_employee e ON rl.employee_id = e.employee_id 
              WHERE rl.purchase_item_id = ? 
              ORDER BY rl.return_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_item_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get back order history
 */
function getBackOrderHistory($conn, $purchase_item_id) {
    $query = "SELECT bol.*, e.first_name, e.last_name 
              FROM tbl_back_order_list bol 
              JOIN tbl_employee e ON bol.employee_id = e.employee_id 
              WHERE bol.purchase_item_id = ? 
              ORDER BY bol.backorder_created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $purchase_item_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get transaction history
 */
function getTransactionHistory($conn, $type = null) {
    $query = "SELECT * FROM tbl_transaction_log";
    if ($type) {
        $query .= " WHERE transaction_type = ?";
    }
    $query .= " ORDER BY transaction_date DESC";
    
    if ($type) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $type);
        $stmt->execute();
        return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    } else {
        $result = $conn->query($query);
        return $result->fetch_all(MYSQLI_ASSOC);
    }
}

/**
 * Get employee details
 */
function getEmployeeDetails($conn, $employee_id) {
    $query = "SELECT * FROM tbl_employee WHERE employee_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

/**
 * Get all employees
 */
function getAllEmployees($conn) {
    $query = "SELECT * FROM tbl_employee ORDER BY first_name, last_name";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get employees by role
 */
function getEmployeesByRole($conn, $job_name) {
    $query = "SELECT * FROM tbl_employee WHERE job_name = ? ORDER BY first_name, last_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $job_name);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get purchase orders by status
 */
function getPurchaseOrdersByStatus($conn, $status) {
    $query = "SELECT pol.*, s.supplier_name, e.first_name, e.last_name 
              FROM tbl_purchase_order_list pol 
              JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
              JOIN tbl_employee e ON pol.employee_id = e.employee_id 
              WHERE pol.status = ? 
              ORDER BY pol.purchase_order_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get returns by status
 */
function getReturnsByStatus($conn, $status) {
    $query = "SELECT rl.*, pol.purchase_order_id, s.supplier_name, e.first_name, e.last_name, 
                     pi.quantity_ordered, pi.quantity_received, il.name as item_name
              FROM tbl_return_list rl
              JOIN tbl_purchase_items pi ON rl.purchase_item_id = pi.purchase_item_id
              JOIN tbl_purchase_order_list pol ON pi.purchase_order_id = pol.purchase_order_id
              JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id
              JOIN tbl_employee e ON rl.employee_id = e.employee_id
              JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id
              JOIN tbl_item_list il ON ri.item_id = il.item_id
              WHERE rl.status = ?
              ORDER BY rl.return_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get back orders by status
 */
function getBackOrdersByStatus($conn, $status) {
    $query = "SELECT bol.*, pol.purchase_order_id, s.supplier_name, il.name as item_name, e.first_name, e.last_name 
              FROM tbl_back_order_list bol 
              JOIN tbl_purchase_items pi ON bol.purchase_item_id = pi.purchase_item_id 
              JOIN tbl_purchase_order_list pol ON pi.purchase_order_id = pol.purchase_order_id 
              JOIN tbl_suppliers s ON pol.supplier_id = s.supplier_id 
              JOIN tbl_raw_ingredients ri ON pi.raw_ingredient_id = ri.raw_ingredient_id 
              JOIN tbl_item_list il ON ri.item_id = il.item_id 
              JOIN tbl_employee e ON bol.employee_id = e.employee_id 
              WHERE bol.status = ?
              ORDER BY bol.backorder_created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $status);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get items by reorder point
 */
function getItemsByReorderPoint($conn) {
    $query = "SELECT ri.*, il.name, il.unit_of_measure
              FROM tbl_raw_ingredients ri
              JOIN tbl_item_list il ON ri.item_id = il.item_id
              WHERE ri.current_stock <= ri.reorder_point
              ORDER BY ri.current_stock ASC";
    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get items by category and reorder point
 */
function getItemsByCategoryAndReorderPoint($conn, $category_id) {
    $query = "SELECT ri.*, il.name, il.unit_of_measure
              FROM tbl_raw_ingredients ri
              JOIN tbl_item_list il ON ri.item_id = il.item_id
              WHERE ri.current_stock <= ri.reorder_point
              AND il.category_id = ?
              ORDER BY ri.current_stock ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $category_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get items by supplier and reorder point
 */
function getItemsBySupplierAndReorderPoint($conn, $supplier_id) {
    $query = "SELECT ri.*, il.name, il.unit_of_measure
              FROM tbl_raw_ingredients ri
              JOIN tbl_item_list il ON ri.item_id = il.item_id
              WHERE ri.current_stock <= ri.reorder_point
              AND il.supplier_id = ?
              ORDER BY ri.current_stock ASC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $supplier_id);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get transaction history by date range
 */
function getTransactionHistoryByDateRange($conn, $start_date, $end_date) {
    $query = "SELECT * FROM tbl_transaction_log 
              WHERE transaction_date BETWEEN ? AND ? 
              ORDER BY transaction_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get transaction history by type and date range
 */
function getTransactionHistoryByTypeAndDateRange($conn, $type, $start_date, $end_date) {
    $query = "SELECT * FROM tbl_transaction_log 
              WHERE transaction_type = ? 
              AND transaction_date BETWEEN ? AND ? 
              ORDER BY transaction_date DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $type, $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get transaction history by employee
 */
function getTransactionHistoryByEmployee($conn, $employee_id) {
    $query = "SELECT * FROM tbl_transaction_log 
              WHERE transaction_description LIKE ? 
              ORDER BY transaction_date DESC";
    $search = "%(by " . getEmployeeName($conn, $employee_id) . ")%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $search);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get transaction history by employee and date range
 */
function getTransactionHistoryByEmployeeAndDateRange($conn, $employee_id, $start_date, $end_date) {
    $query = "SELECT * FROM tbl_transaction_log 
              WHERE transaction_description LIKE ? 
              AND transaction_date BETWEEN ? AND ? 
              ORDER BY transaction_date DESC";
    $search = "%(by " . getEmployeeName($conn, $employee_id) . ")%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("sss", $search, $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get transaction history by employee and type
 */
function getTransactionHistoryByEmployeeAndType($conn, $employee_id, $type) {
    $query = "SELECT * FROM tbl_transaction_log 
              WHERE transaction_type = ? 
              AND transaction_description LIKE ? 
              ORDER BY transaction_date DESC";
    $search = "%(by " . getEmployeeName($conn, $employee_id) . ")%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ss", $type, $search);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

/**
 * Get transaction history by employee, type and date range
 */
function getTransactionHistoryByEmployeeTypeAndDateRange($conn, $employee_id, $type, $start_date, $end_date) {
    $query = "SELECT * FROM tbl_transaction_log 
              WHERE transaction_type = ? 
              AND transaction_description LIKE ? 
              AND transaction_date BETWEEN ? AND ? 
              ORDER BY transaction_date DESC";
    $search = "%(by " . getEmployeeName($conn, $employee_id) . ")%";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssss", $type, $search, $start_date, $end_date);
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
} 