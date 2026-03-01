<?php
/**
 * PIERRE GASLY - Orders Management (Improved Layout)
 * Modern, clean design with better organization
 */

require_once 'includes/config.php';
requireAdmin();

$pageTitle = 'Orders Management';
$db = Database::getInstance();

$success = '';
$error = '';

// Get all active riders
$riders = $db->fetchAll("SELECT u.user_id, u.full_name FROM users u WHERE u.role = 'rider' AND u.status = 'active' ORDER BY u.full_name");

// Handle Assign Rider
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assign_rider'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        $order_id = (int)$_POST['order_id'];
        $rider_id = (int)$_POST['rider_id'];
        
        $sql = "UPDATE orders SET rider_id = ?, order_status = 'preparing', prepared_at = NOW(), updated_by = ? WHERE order_id = ?";
        
        if ($db->query($sql, [$rider_id, $_SESSION['user_id'], $order_id])) {
            $success = 'Rider assigned successfully!';
            logActivity('update', 'order', $order_id, "Assigned rider to order");
        } else {
            $error = 'Failed to assign rider';
        }
    }
}

// Handle Update Status
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!verifyCSRFToken($_POST['csrf_token'])) {
        $error = 'Invalid security token';
    } else {
        $order_id = (int)$_POST['order_id'];
        $new_status = $_POST['new_status'];
        
        $timestamp_field = '';
        switch ($new_status) {
            case 'preparing':
                $timestamp_field = 'prepared_at = NOW()';
                break;
            case 'out_for_delivery':
                $timestamp_field = 'out_for_delivery_at = NOW()';
                break;
            case 'delivered':
                $timestamp_field = 'delivered_at = NOW()';
                $order = $db->fetchOne("SELECT * FROM orders WHERE order_id = ?", [$order_id]);
                $sale_sql = "INSERT INTO sales (order_id, rider_id, sale_amount, sale_date) VALUES (?, ?, ?, CURDATE())";
                $db->query($sale_sql, [$order_id, $order['rider_id'], $order['total_amount']]);
                break;
            case 'cancelled':
                $timestamp_field = 'cancelled_at = NOW()';
                break;
        }
        
        $sql = "UPDATE orders SET order_status = ?, $timestamp_field, updated_by = ? WHERE order_id = ?";
        
        if ($db->query($sql, [$new_status, $_SESSION['user_id'], $order_id])) {
            $success = 'Order status updated successfully!';
            logActivity('update', 'order', $order_id, "Updated status to: $new_status");
        } else {
            $error = 'Failed to update status';
        }
    }
}

// Get filter
$status_filter = $_GET['status'] ?? 'all';

// Build query
$where = "WHERE 1=1";
$params = [];

if ($status_filter !== 'all') {
    $where .= " AND o.order_status = ?";
    $params[] = $status_filter;
}

// Get orders
$sql = "SELECT o.*, 
        cu.full_name as customer_name, cu.phone as customer_phone,
        p.product_name, p.size_kg,
        b.brand_name,
        r.full_name as rider_name
        FROM orders o
        JOIN users cu ON o.customer_id = cu.user_id
        JOIN products p ON o.product_id = p.product_id
        JOIN brands b ON p.brand_id = b.brand_id
        LEFT JOIN users r ON o.rider_id = r.user_id
        $where
        ORDER BY o.ordered_at DESC";

$orders = $db->fetchAll($sql, $params);

// Get statistics
$stats = [
    'all' => count($db->fetchAll("SELECT order_id FROM orders")),
    'pending' => count($db->fetchAll("SELECT order_id FROM orders WHERE order_status = 'pending'")),
    'preparing' => count($db->fetchAll("SELECT order_id FROM orders WHERE order_status = 'preparing'")),
    'out_for_delivery' => count($db->fetchAll("SELECT order_id FROM orders WHERE order_status = 'out_for_delivery'")),
    'delivered' => count($db->fetchAll("SELECT order_id FROM orders WHERE order_status = 'delivered'")),
    'cancelled' => count($db->fetchAll("SELECT order_id FROM orders WHERE order_status = 'cancelled'"))
];

// Get single order for details
$viewOrder = null;
if (isset($_GET['view'])) {
    $sql = "SELECT o.*, 
            cu.full_name as customer_name, cu.phone as customer_phone, cu.email as customer_email,
            p.product_name, p.size_kg, p.price,
            b.brand_name,
            r.full_name as rider_name, r.phone as rider_phone
            FROM orders o
            JOIN users cu ON o.customer_id = cu.user_id
            JOIN products p ON o.product_id = p.product_id
            JOIN brands b ON p.brand_id = b.brand_id
            LEFT JOIN users r ON o.rider_id = r.user_id
            WHERE o.order_id = ?";
    $viewOrder = $db->fetchOne($sql, [(int)$_GET['view']]);
}

$csrfToken = generateCSRFToken();
include 'includes/header.php';
?>

<style>
/* Enhanced Orders Styling */
.orders-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
}

.orders-stats {
    display: flex;
    gap: 15px;
}

.orders-filter-bar {
    background: white;
    padding: 20px 24px;
    border-radius: 16px;
    margin-bottom: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    align-items: center;
}

.filter-chip {
    padding: 10px 20px;
    border: 2px solid #e2e8f0;
    background: white;
    border-radius: 24px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    transition: all 0.3s;
    text-decoration: none;
    color: #4a5568;
    display: flex;
    align-items: center;
    gap: 8px;
}

.filter-chip:hover {
    border-color: #667eea;
    background: #f5f7ff;
    transform: translateY(-2px);
}

.filter-chip.active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-color: transparent;
}

.count-badge {
    background: rgba(255,255,255,0.3);
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: 700;
}

.filter-chip.active .count-badge {
    background: rgba(255,255,255,0.2);
}

/* Enhanced Order Cards */
.orders-grid {
    display: grid;
    gap: 20px;
}

.order-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.06);
    transition: all 0.3s;
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
}

.order-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    width: 6px;
    height: 100%;
}

.order-card.status-pending::before { background: linear-gradient(135deg, #ff9800 0%, #ffa726 100%); }
.order-card.status-preparing::before { background: linear-gradient(135deg, #2196f3 0%, #42a5f5 100%); }
.order-card.status-out_for_delivery::before { background: linear-gradient(135deg, #9c27b0 0%, #ba68c8 100%); }
.order-card.status-delivered::before { background: linear-gradient(135deg, #4caf50 0%, #66bb6a 100%); }
.order-card.status-cancelled::before { background: linear-gradient(135deg, #f44336 0%, #ef5350 100%); }

.order-card:hover {
    transform: translateY(-4px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.12);
    border-color: #667eea;
}

.order-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid #f0f0f0;
}

.order-number-section {
    flex: 1;
}

.order-number {
    font-size: 20px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 6px;
}

.order-date {
    font-size: 13px;
    color: #718096;
}

.order-card-body {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.order-info-group {
    background: #f7fafc;
    padding: 16px;
    border-radius: 12px;
}

.info-label {
    font-size: 12px;
    color: #718096;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 8px;
}

.info-value {
    font-size: 15px;
    color: #2d3748;
    font-weight: 600;
}

.info-value.highlight {
    font-size: 20px;
    color: #4caf50;
    font-weight: 800;
}

.order-card-footer {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.rider-badge {
    background: linear-gradient(135deg, #e3f2fd 0%, #f3e5f5 100%);
    padding: 10px 16px;
    border-radius: 24px;
    font-size: 13px;
    font-weight: 600;
    color: #667eea;
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

/* Modal Improvements */
.modal-detail-section {
    margin-bottom: 24px;
}

.modal-detail-section h4 {
    font-size: 14px;
    font-weight: 700;
    color: #667eea;
    margin-bottom: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.detail-grid {
    display: grid;
    grid-template-columns: 150px 1fr;
    gap: 12px;
}

.detail-row {
    display: contents;
}

.detail-label {
    font-weight: 600;
    color: #718096;
    font-size: 14px;
}

.detail-value {
    color: #2d3748;
    font-size: 14px;
}
</style>

<div class="page-header">
    <h1>🛍️ Orders Management</h1>
    <p>Manage and track all customer orders</p>
</div>

<?php if ($success): ?>
    <div class="alert alert-success">
        <span style="font-size: 20px;">✓</span>
        <span><?php echo $success; ?></span>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-error">
        <span style="font-size: 20px;">✗</span>
        <span><?php echo $error; ?></span>
    </div>
<?php endif; ?>

<!-- Enhanced Filter Bar -->
<div class="orders-filter-bar">
    <a href="?status=all" class="filter-chip <?php echo $status_filter == 'all' ? 'active' : ''; ?>">
        📦 All Orders
        <span class="count-badge"><?php echo $stats['all']; ?></span>
    </a>
    <a href="?status=pending" class="filter-chip <?php echo $status_filter == 'pending' ? 'active' : ''; ?>">
        ⏳ Pending
        <span class="count-badge"><?php echo $stats['pending']; ?></span>
    </a>
    <a href="?status=preparing" class="filter-chip <?php echo $status_filter == 'preparing' ? 'active' : ''; ?>">
        👨‍🍳 Preparing
        <span class="count-badge"><?php echo $stats['preparing']; ?></span>
    </a>
    <a href="?status=out_for_delivery" class="filter-chip <?php echo $status_filter == 'out_for_delivery' ? 'active' : ''; ?>">
        🚚 Out for Delivery
        <span class="count-badge"><?php echo $stats['out_for_delivery']; ?></span>
    </a>
    <a href="?status=delivered" class="filter-chip <?php echo $status_filter == 'delivered' ? 'active' : ''; ?>">
        ✅ Delivered
        <span class="count-badge"><?php echo $stats['delivered']; ?></span>
    </a>
    <a href="?status=cancelled" class="filter-chip <?php echo $status_filter == 'cancelled' ? 'active' : ''; ?>">
        ❌ Cancelled
        <span class="count-badge"><?php echo $stats['cancelled']; ?></span>
    </a>
</div>

<!-- Orders Grid -->
<?php if (empty($orders)): ?>
    <div class="empty-state" style="background: white; padding: 80px 40px; border-radius: 20px; text-align: center;">
        <div style="font-size: 80px; margin-bottom: 20px; opacity: 0.5;">📦</div>
        <h3 style="font-size: 24px; margin-bottom: 12px; color: #2d3748;">No Orders Found</h3>
        <p style="color: #718096;">Orders will appear here when customers place them</p>
    </div>
<?php else: ?>
    <div class="orders-grid">
        <?php foreach ($orders as $order): ?>
        <div class="order-card status-<?php echo $order['order_status']; ?>">
            <div class="order-card-header">
                <div class="order-number-section">
                    <div class="order-number">#<?php echo htmlspecialchars($order['order_number']); ?></div>
                    <div class="order-date">📅 <?php echo formatDateTime($order['ordered_at']); ?></div>
                </div>
                <div>
                    <?php
                    $statusColors = [
                        'pending' => 'warning',
                        'preparing' => 'info',
                        'out_for_delivery' => 'primary',
                        'delivered' => 'success',
                        'cancelled' => 'danger'
                    ];
                    $color = $statusColors[$order['order_status']] ?? 'secondary';
                    ?>
                    <span class="badge badge-<?php echo $color; ?>" style="font-size: 14px; padding: 8px 16px;">
                        <?php echo ucwords(str_replace('_', ' ', $order['order_status'])); ?>
                    </span>
                </div>
            </div>
            
            <div class="order-card-body">
                <div class="order-info-group">
                    <div class="info-label">Customer</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['customer_name']); ?></div>
                    <div style="font-size: 12px; color: #a0aec0; margin-top: 4px;">
                        📱 <?php echo htmlspecialchars($order['customer_phone']); ?>
                    </div>
                </div>
                
                <div class="order-info-group">
                    <div class="info-label">Product</div>
                    <div class="info-value"><?php echo htmlspecialchars($order['brand_name']); ?></div>
                    <div style="font-size: 13px; color: #718096; margin-top: 4px;">
                        <?php echo htmlspecialchars($order['product_name']); ?> (<?php echo $order['size_kg']; ?>kg)
                    </div>
                </div>
                
                <div class="order-info-group">
                    <div class="info-label">Amount</div>
                    <div class="info-value highlight"><?php echo formatCurrency($order['total_amount']); ?></div>
                    <div style="font-size: 12px; color: #a0aec0; margin-top: 4px;">
                        💳 <?php echo ucfirst($order['payment_method']); ?>
                    </div>
                </div>
            </div>
            
            <?php if ($order['rider_name']): ?>
            <div style="margin-bottom: 16px;">
                <span class="rider-badge">
                    🚚 <?php echo htmlspecialchars($order['rider_name']); ?>
                </span>
            </div>
            <?php endif; ?>
            
            <div class="order-card-footer">
                <a href="?view=<?php echo $order['order_id']; ?>" class="btn-sm btn-primary">
                    📋 View Details
                </a>
                
                <?php if ($order['order_status'] == 'pending'): ?>
                    <button onclick="assignRider(<?php echo $order['order_id']; ?>)" class="btn-sm btn-primary">
                        👤 Assign Rider
                    </button>
                <?php endif; ?>
                
                <?php if ($order['order_status'] != 'delivered' && $order['order_status'] != 'cancelled'): ?>
                    <button onclick="updateStatus(<?php echo $order['order_id']; ?>)" class="btn-sm btn-primary">
                        🔄 Update Status
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<!-- View Order Modal -->
<?php if ($viewOrder): ?>
<div id="viewModal" class="modal active">
    <div class="modal-content">
        <span class="btn-close" onclick="window.location.href='orders.php'">&times;</span>
        <div class="modal-header">
            <h3>📦 Order Details - #<?php echo htmlspecialchars($viewOrder['order_number']); ?></h3>
        </div>
        <div class="modal-body">
            <div class="modal-detail-section">
                <h4>Order Status</h4>
                <span class="badge badge-<?php echo $statusColors[$viewOrder['order_status']] ?? 'secondary'; ?>" style="font-size: 16px; padding: 10px 20px;">
                    <?php echo ucwords(str_replace('_', ' ', $viewOrder['order_status'])); ?>
                </span>
            </div>
            
            <div class="modal-detail-section">
                <h4>Customer Information</h4>
                <div class="detail-grid">
                    <div class="detail-row">
                        <div class="detail-label">Name:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($viewOrder['customer_name']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($viewOrder['customer_phone']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($viewOrder['customer_email']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Address:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($viewOrder['delivery_address']); ?></div>
                    </div>
                </div>
            </div>
            
            <div class="modal-detail-section">
                <h4>Product Details</h4>
                <div class="detail-grid">
                    <div class="detail-row">
                        <div class="detail-label">Product:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($viewOrder['brand_name'] . ' ' . $viewOrder['product_name']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Size:</div>
                        <div class="detail-value"><?php echo $viewOrder['size_kg']; ?>kg</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Quantity:</div>
                        <div class="detail-value"><?php echo $viewOrder['quantity']; ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Price:</div>
                        <div class="detail-value"><?php echo formatCurrency($viewOrder['price']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Total:</div>
                        <div class="detail-value" style="font-size: 20px; font-weight: 700; color: #4caf50;">
                            <?php echo formatCurrency($viewOrder['total_amount']); ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($viewOrder['rider_name']): ?>
            <div class="modal-detail-section">
                <h4>Delivery Rider</h4>
                <div class="detail-grid">
                    <div class="detail-row">
                        <div class="detail-label">Rider:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($viewOrder['rider_name']); ?></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Contact:</div>
                        <div class="detail-value"><?php echo htmlspecialchars($viewOrder['rider_phone']); ?></div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Assign Rider Modal -->
<div id="assignRiderModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="btn-close" onclick="document.getElementById('assignRiderModal').classList.remove('active')">&times;</span>
        <div class="modal-header">
            <h3>👤 Assign Delivery Rider</h3>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="order_id" id="assign_order_id">
                
                <div class="form-group">
                    <label>Select Rider *</label>
                    <select name="rider_id" required class="form-control">
                        <option value="">Choose a rider...</option>
                        <?php foreach ($riders as $rider): ?>
                            <option value="<?php echo $rider['user_id']; ?>">
                                <?php echo htmlspecialchars($rider['full_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <button type="submit" name="assign_rider" class="btn btn-primary" style="width: 100%;">
                    Assign Rider
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateStatusModal" class="modal">
    <div class="modal-content" style="max-width: 400px;">
        <span class="btn-close" onclick="document.getElementById('updateStatusModal').classList.remove('active')">&times;</span>
        <div class="modal-header">
            <h3>🔄 Update Order Status</h3>
        </div>
        <div class="modal-body">
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                <input type="hidden" name="order_id" id="status_order_id">
                
                <div class="form-group">
                    <label>New Status *</label>
                    <select name="new_status" required class="form-control">
                        <option value="">Choose status...</option>
                        <option value="pending">⏳ Pending</option>
                        <option value="preparing">👨‍🍳 Preparing</option>
                        <option value="out_for_delivery">🚚 Out for Delivery</option>
                        <option value="delivered">✅ Delivered</option>
                        <option value="cancelled">❌ Cancelled</option>
                    </select>
                </div>
                
                <button type="submit" name="update_status" class="btn btn-primary" style="width: 100%;">
                    Update Status
                </button>
            </form>
        </div>
    </div>
</div>

<script>
function assignRider(orderId) {
    document.getElementById('assign_order_id').value = orderId;
    document.getElementById('assignRiderModal').classList.add('active');
}

function updateStatus(orderId) {
    document.getElementById('status_order_id').value = orderId;
    document.getElementById('updateStatusModal').classList.add('active');
}
</script>

<style>
/* Modal & Form Styles */
.modal { display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); backdrop-filter: blur(4px); z-index: 9999; align-items: center; justify-content: center; }
.modal.active { display: flex; }
.modal-content { background: white; border-radius: 20px; padding: 0; width: 90%; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
.modal-header { padding: 25px 30px; border-bottom: 1px solid #e8ecf1; background: linear-gradient(135deg, #f5f7fa 0%, #ffffff 100%); }
.modal-header h3 { font-size: 22px; margin: 0; color: #2d3748; font-weight: 700; }
.btn-close { float: right; font-size: 28px; cursor: pointer; color: #a0aec0; line-height: 1; transition: all 0.2s; }
.btn-close:hover { color: #667eea; transform: rotate(90deg); }
.modal-body { padding: 30px; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; margin-bottom: 8px; font-weight: 600; font-size: 14px; color: #4a5568; }
.form-control { width: 100%; padding: 12px 16px; border: 2px solid #e2e8f0; border-radius: 10px; font-size: 15px; transition: all 0.3s; }
.form-control:focus { outline: none; border-color: #667eea; box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1); }
.btn { padding: 12px 24px; border: none; border-radius: 10px; font-size: 15px; font-weight: 600; cursor: pointer; transition: all 0.3s; }
.btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3); }
.btn-primary:hover { transform: translateY(-2px); box-shadow: 0 6px 16px rgba(102, 126, 234, 0.4); }
.btn-sm { padding: 8px 16px; font-size: 13px; }
.alert { padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; }
.alert-success { background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%); color: #2e7d32; border: 1px solid #a5d6a7; }
.alert-error { background: linear-gradient(135deg, #ffebee 0%, #ffcdd2 100%); color: #c62828; border: 1px solid #ef9a9a; }
</style>

<?php include 'includes/footer.php'; ?>
