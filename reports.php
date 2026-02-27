<?php
/**
 * PIERRE GASLY - Reports & Analytics
 * Business intelligence and reporting
 */

require_once 'includes/config.php';
requireAdmin();

$pageTitle = 'Reports & Analytics';
$db = Database::getInstance();

// Get date range
$start_date = $_GET['start'] ?? date('Y-m-01');
$end_date = $_GET['end'] ?? date('Y-m-t');

// Revenue by Date
$revenue_data = $db->fetchAll("
    SELECT DATE(sale_date) as date, SUM(sale_amount) as revenue, COUNT(*) as orders
    FROM sales
    WHERE sale_date BETWEEN ? AND ?
    GROUP BY DATE(sale_date)
    ORDER BY date
", [$start_date, $end_date]);

// Top Products
$top_products = $db->fetchAll("
    SELECT p.product_name, b.brand_name, p.size_kg, COUNT(o.order_id) as orders, SUM(o.total_amount) as revenue
    FROM orders o
    JOIN products p ON o.product_id = p.product_id
    JOIN brands b ON p.brand_id = b.brand_id
    WHERE o.order_status = 'delivered'
    GROUP BY p.product_id
    ORDER BY orders DESC
    LIMIT 5
", []);

// Top Riders
$top_riders = $db->fetchAll("
    SELECT r.full_name, COUNT(s.sale_id) as deliveries, SUM(s.sale_amount) as revenue
    FROM sales s
    JOIN users r ON s.rider_id = r.user_id
    WHERE s.sale_date BETWEEN ? AND ?
    GROUP BY s.rider_id
    ORDER BY deliveries DESC
    LIMIT 5
", [$start_date, $end_date]);

// Top Customers
$top_customers = $db->fetchAll("
    SELECT u.full_name, COUNT(o.order_id) as orders, SUM(o.total_price) as total_spent
    FROM orders o
    JOIN users u ON o.customer_id = u.user_id
    WHERE o.order_status = 'delivered'
    GROUP BY o.customer_id
    ORDER BY total_spent DESC
    LIMIT 5
", []);

// Summary Stats
$summary = $db->fetchOne("
    SELECT 
        COUNT(DISTINCT o.customer_id) as total_customers,
        COUNT(o.order_id) as total_orders,
        COALESCE(SUM(o.total_price), 0) as total_revenue,
        COALESCE(AVG(o.total_price), 0) as avg_order_value
    FROM orders o
    WHERE o.order_status = 'delivered'
", []);

include 'includes/header.php';
?>

<div class="page-header">
    <h1>📈 Reports & Analytics</h1>
    <p>Business insights and performance metrics</p>
</div>

<!-- Date Range Filter -->
<div class="dashboard-card" style="margin-bottom: 25px;">
    <div class="card-body">
        <form method="GET" action="" style="display: flex; gap: 15px; align-items: end;">
            <div class="form-group" style="margin: 0;">
                <label>Start Date</label>
                <input type="date" name="start" value="<?php echo $start_date; ?>" class="form-control">
            </div>
            <div class="form-group" style="margin: 0;">
                <label>End Date</label>
                <input type="date" name="end" value="<?php echo $end_date; ?>" class="form-control">
            </div>
            <button type="submit" class="btn btn-primary">Generate Report</button>
        </form>
    </div>
</div>

<!-- Summary Stats -->
<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon">💰</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo formatCurrency($summary['total_revenue'] ?? 0); ?></div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
    
    <div class="stat-card stat-success">
        <div class="stat-icon">📦</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo number_format($summary['total_orders'] ?? 0); ?></div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    
    <div class="stat-card stat-info">
        <div class="stat-icon">👥</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo number_format($summary['total_customers'] ?? 0); ?></div>
            <div class="stat-label">Customers</div>
        </div>
    </div>
    
    <div class="stat-card stat-warning">
        <div class="stat-icon">📊</div>
        <div class="stat-details">
            <div class="stat-value"><?php echo formatCurrency($summary['avg_order_value'] ?? 0); ?></div>
            <div class="stat-label">Avg Order Value</div>
        </div>
    </div>
</div>

<!-- Reports Grid -->
<div class="dashboard-grid">
    <!-- Top Products -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>🏆 Top Products</h2>
        </div>
        <div class="card-body">
            <?php if (empty($top_products)): ?>
                <p style="text-align: center; color: #999;">No data available</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Product</th>
                            <th>Orders</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_products as $product): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($product['brand_name'] . ' ' . $product['product_name'] . ' ' . $product['size_kg'] . 'kg'); ?></strong></td>
                            <td><?php echo $product['orders']; ?></td>
                            <td><?php echo formatCurrency($product['revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Riders -->
    <div class="dashboard-card">
        <div class="card-header">
            <h2>🚚 Top Riders</h2>
        </div>
        <div class="card-body">
            <?php if (empty($top_riders)): ?>
                <p style="text-align: center; color: #999;">No data available</p>
            <?php else: ?>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Rider</th>
                            <th>Deliveries</th>
                            <th>Revenue</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_riders as $rider): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($rider['full_name']); ?></strong></td>
                            <td><?php echo $rider['deliveries']; ?></td>
                            <td><?php echo formatCurrency($rider['revenue']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Top Customers -->
<div class="dashboard-card">
    <div class="card-header">
        <h2>⭐ Top Customers</h2>
    </div>
    <div class="card-body">
        <?php if (empty($top_customers)): ?>
            <p style="text-align: center; color: #999;">No data available</p>
        <?php else: ?>
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Total Orders</th>
                        <th>Total Spent</th>
                        <th>Avg Order Value</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($top_customers as $customer): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($customer['full_name']); ?></strong></td>
                        <td><?php echo $customer['orders']; ?></td>
                        <td><?php echo formatCurrency($customer['total_spent']); ?></td>
                        <td><?php echo formatCurrency($customer['total_spent'] / $customer['orders']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<style>
.form-group label {
    display: block;
    margin-bottom: 6px;
    font-weight: 600;
    font-size: 14px;
}

.form-control {
    padding: 10px 12px;
    border: 2px solid #e0e0e0;
    border-radius: 6px;
    font-size: 14px;
}

.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}
</style>

<?php include 'includes/footer.php'; ?>
