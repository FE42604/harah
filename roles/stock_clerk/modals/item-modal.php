<?php
// Fetch all active categories
$categories_query = "SELECT * FROM tbl_categories ORDER BY category_name";
$categories_result = $conn->query($categories_query);

// Fetch all active suppliers
$suppliers_query = "SELECT * FROM tbl_suppliers ORDER BY supplier_name";
$suppliers_result = $conn->query($suppliers_query);
?>

<!-- Create Item Modal -->
<div class="modal fade" id="createItemModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add New Item</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="functions/item-function.php" method="POST">
                <input type="hidden" name="action" value="create">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="name" class="form-label">Item Name</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Description</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="category_id" class="form-label">Category</label>
                        <select class="form-select" id="category_id" name="category_id" required>
                            <option value="">Select Category</option>
                            <?php while ($category = $categories_result->fetch_assoc()): ?>
                                <option value="<?php echo $category['category_id']; ?>">
                                    <?php echo htmlspecialchars($category['category_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="supplier_id" class="form-label">Supplier</label>
                        <select class="form-select" id="supplier_id" name="supplier_id" required>
                            <option value="">Select Supplier</option>
                            <?php while ($supplier = $suppliers_result->fetch_assoc()): ?>
                                <option value="<?php echo $supplier['supplier_id']; ?>">
                                    <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="cost" class="form-label">Cost</label>
                        <input type="number" step="0.01" class="form-control" id="cost" name="cost" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Create Item</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Reset result pointers for edit modals
$categories_result->data_seek(0);
$suppliers_result->data_seek(0);

// Create edit and view modals for each item
if ($result && $result->num_rows > 0):
    $result->data_seek(0); // Reset the result pointer
    while ($item = $result->fetch_assoc()):
?>
    <!-- Edit Item Modal -->
    <div class="modal fade" id="editItemModal<?php echo $item['item_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Item</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="functions/item-function.php" method="POST">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="item_id" value="<?php echo $item['item_id']; ?>">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="edit_name<?php echo $item['item_id']; ?>" class="form-label">Item Name</label>
                            <input type="text" class="form-control" id="edit_name<?php echo $item['item_id']; ?>" 
                                   name="name" value="<?php echo htmlspecialchars($item['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="edit_description<?php echo $item['item_id']; ?>" class="form-label">Description</label>
                            <textarea class="form-control" id="edit_description<?php echo $item['item_id']; ?>" 
                                      name="description" rows="3"><?php echo htmlspecialchars($item['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="edit_category_id<?php echo $item['item_id']; ?>" class="form-label">Category</label>
                            <select class="form-select" id="edit_category_id<?php echo $item['item_id']; ?>" name="category_id" required>
                                <?php 
                                $categories_result->data_seek(0);
                                while ($category = $categories_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $category['category_id']; ?>" 
                                            <?php echo $category['category_id'] == $item['category_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['category_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_supplier_id<?php echo $item['item_id']; ?>" class="form-label">Supplier</label>
                            <select class="form-select" id="edit_supplier_id<?php echo $item['item_id']; ?>" name="supplier_id" required>
                                <?php 
                                $suppliers_result->data_seek(0);
                                while ($supplier = $suppliers_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $supplier['supplier_id']; ?>" 
                                            <?php echo $supplier['supplier_id'] == $item['supplier_id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($supplier['supplier_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="edit_cost<?php echo $item['item_id']; ?>" class="form-label">Cost</label>
                            <input type="number" step="0.01" class="form-control" id="edit_cost<?php echo $item['item_id']; ?>" 
                                   name="cost" value="<?php echo $item['cost']; ?>" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Item Modal -->
    <div class="modal fade" id="viewItemModal<?php echo $item['item_id']; ?>" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Item Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Item Name:</strong> <?php echo htmlspecialchars($item['name']); ?></p>
                    <p><strong>Description:</strong> <?php echo htmlspecialchars($item['description']); ?></p>
                    <p><strong>Category:</strong> <?php echo htmlspecialchars($item['category_name']); ?></p>
                    <p><strong>Supplier:</strong> <?php echo htmlspecialchars($item['supplier_name']); ?></p>
                    <p><strong>Cost:</strong> ₱<?php echo number_format($item['cost'], 2); ?></p>
                    <p><strong>Status:</strong> <?php echo htmlspecialchars($item['status']); ?></p>
                    <p><strong>Created By:</strong> <?php echo htmlspecialchars($item['employee_name']); ?></p>
                    <p><strong>Created At:</strong> <?php echo date('M d, Y H:i', strtotime($item['date_created'])); ?></p>
                    <p><strong>Last Updated:</strong> <?php echo date('M d, Y H:i', strtotime($item['date_updated'])); ?></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
<?php 
    endwhile;
endif;
?> 